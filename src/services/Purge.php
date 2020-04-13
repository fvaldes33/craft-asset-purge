<?php
/**
 * Asset Purge plugin for Craft CMS 3.x
 *
 * Provides an interface to view unused assets.
 *
 * @link      https://github.com/fvaldes33
 * @copyright Copyright (c) 2020 fvaldes33
 */

namespace fvaldes33\assetpurge\services;

use benf\neo\elements\Block;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\helpers\StringHelper;
use verbb\supertable\elements\SuperTableBlockElement;

/**
 * @author    fvaldes33
 * @package   AssetPurge
 * @since     1.0.0
 */
class Purge extends Component
{
    protected $isSuperTableInstalled = false;
    protected $isNeoInstalled = false;
    protected $acceptableParents = [
        Category::class,
        Entry::class,
        Tag::class,
        GlobalSet::class
    ];

    // Public Methods
    // =========================================================================
    public function init()
    {
        $this->isSuperTableInstalled = class_exists('verbb\\supertable\\elements\\SuperTableBlockElement');
        $this->isNeoInstalled = class_exists('benf\\neo\\elements\\Block');
    }

    /*
     * @return mixed
     */
    public function alterAssetQuery(ElementQuery &$query)
    {
        $query->kind = null;

        array_pop($query->subQuery->where);

        $ids = $this->_getOrphanIds();

        $query->subQuery->where[] = ['not in', 'elements.id', $ids];

        return $query;
    }

    // Private Methods
    // =========================================================================

    private function _getOrphanIds(): array
    {
        // don't delete users avatar image
        $userSubQuery = (new Query())
            ->select('users.photoId as id')
            ->from('{{%users}} users')
            ->column();

        // Normal relationships
        // Get all non soft deletes and revisions
        $subQuery = (new Query())
            ->select([
                'id' => 'relations.targetId',
                'source' => 'relations.sourceId',
                'type' => 'elements.type'
            ])
            ->from('{{%relations}} relations')
            ->innerJoin('{{%elements}} elements', '[[relations.sourceId]] = [[elements.id]]')
            ->where([
                "elements.revisionId" => null,
                "elements.dateDeleted" => null
            ]);

        // placeholder
        $elementIds = [];

        // edge case time
        foreach ($subQuery->all() as $row) {
            switch ($row['type']) {
                case MatrixBlock::class:
                    $block = MatrixBlock::find()
                        ->id($row['source'])
                        ->anyStatus()
                        ->one();

                    $owner = $this->_getAcceptableParent($block);
                    if (method_exists($owner, 'getIsRevision') && !$owner->getIsRevision()) {
                        $elementIds[] = $row['id'];
                    }
                    break;
                case 'verbb\supertable\elements\SuperTableBlockElement':
                    // just in case the plugin was deleted
                    if (!$this->isSuperTableInstalled) {
                        continue;
                    }
                    $block = SuperTableBlockElement::find()
                        ->id($row['source'])
                        ->anyStatus()
                        ->one();
                    $owner = $this->_getAcceptableParent($block);
                    if (method_exists($owner, 'getIsRevision') && !$owner->getIsRevision()) {
                        $elementIds[] = $row['id'];
                    }
                    // todo: check for nested matrix blocks
                    break;
                case 'benf\neo\elements\Block':
                    // just in case the plugin was deleted
                    if (!$this->isNeoInstalled) {
                        continue;
                    }
                    $block = Block::find()
                        ->id($row['source'])
                        ->anyStatus()
                        ->one();
                    $owner = $this->_getAcceptableParent($block);
                    if (method_exists($owner, 'getIsRevision') && !$owner->getIsRevision()) {
                        $elementIds[] = $row['id'];
                    }
                    // todo: check for nested matrix blocks
                    break;
                default:
                    $elementIds[] = $row['id'];
                    break;
            }
        }

        /**
         * Redactors can have refs to assets, so we will need to
         * find all possible redactor content.
         *
         * Test for redactor fields in:
         * Entries
         * MatrixBlocks
         * Supertables
         * NeoBlocks
         * Matrix inside of Supertable
         * Supertable inside of matrix
         * Matrix or Supertable nested in NeoBlocks
         *
         * Not tested on:
         * Supertable -> Matrix -> Supertable
         * Matrix -> Supertable -> Matrix
         */
        $redactorIds = $this->_getRedactorAssets();

        // todo: linkit support
        // $linkitIds = $this->_getLinkitAssets();

        // combine it all
        $excludeIds = array_unique(array_merge($userSubQuery, $elementIds, $redactorIds));

        return $excludeIds ?? [];
    }

    private function _getRedactorAssets(): array
    {
        $fields = ($this->_baseFieldsQuery())
            ->where(['fields.type' => 'craft\\redactor\\Field']);

        $ids = [];

        foreach ($fields->all() as $field) {
            $context = explode(":", $field['context']);
            switch ($context[0]) {
                case 'global':
                    $ids = array_merge($ids, $this->_findGlobalRedactorContent($field['handle']));
                break;
                case 'matrixBlockType':
                    $ids = array_merge($ids, $this->_findMatrixRedactorContent($field['handle'], $context[1]));
                break;
                case 'superTableBlockType':
                    $ids = array_merge($ids, $this->_findSuperTableRedactorContent($field['handle'], $context[1]));
                break;
            }
        }

        return $ids;
    }

    /**
     * Find redactor instances inside supertable blocks
     *
     * @param string $handle
     * @param string $blockTypeUid
     * @return array
     */
    private function _findSuperTableRedactorContent(string $handle, string $blockTypeUid): array
    {
        // just in case the plugin was deleted
        if (!$this->isSuperTableInstalled) {
            return [];
        }

        $blockTypeQuery = (new Query())
            ->select([
                'typeId' => 'bt.id',
                'fieldId' => 'bt.fieldId',
            ])
            ->from(['bt' => '{{%supertableblocktypes}}'])
            ->where(['bt.uid' => $blockTypeUid]);
        $blockType = $blockTypeQuery->one();

        $superTableBlocks = SuperTableBlockElement::find()
            ->fieldId($blockType['fieldId'])
            ->typeId($blockType['typeId'])
            ->all();

        $rows = [];
        foreach ($superTableBlocks as $key => $block) {
            $owner = $this->_getAcceptableParent($block);
            if (method_exists($owner, 'getIsRevision') && !$owner->getIsRevision()) {
                $rows[] = [
                    'field' => $block->{$handle} ? $block->{$handle}->getRawContent() : ''
                ];
            }
        }

        $ids = array_merge([], $this->_getIdFromRef($rows));

        return array_unique($ids);
    }

    /**
     * Find redactor instances inside matrxblocks
     *
     * @param string $handle
     * @param string $blockTypeUid
     * @return array
     */
    private function _findMatrixRedactorContent(string $handle, string $blockTypeUid): array
    {
        $blockTypeQuery = (new Query())
            ->select([
                'typeId' => 'matrixblocktypes.id',
                'fieldId' => 'matrixblocktypes.fieldId',
                'handle' => 'matrixblocktypes.handle'
            ])
            ->from(['matrixblocktypes' => '{{%matrixblocktypes}}'])
            ->where(['matrixblocktypes.uid' => $blockTypeUid]);
        $blockType = $blockTypeQuery->one();

        $matrixBlocks = MatrixBlock::find()
            ->fieldId($blockType['fieldId'])
            ->typeId($blockType['typeId'])
            ->all();

        $rows = [];
        foreach ($matrixBlocks as $key => $block) {
            $owner = $this->_getAcceptableParent($block);
            if (method_exists($owner, 'getIsRevision') && !$owner->getIsRevision()) {
                $rows[] = [
                    'field' => $block->{$handle} ? $block->{$handle}->getRawContent() : ''
                ];
            }
        }

        $ids = array_merge([], $this->_getIdFromRef($rows));

        return array_unique($ids);
    }

    /**
     * Find redactor instances inside global element types like
     * Entries, Categories, Tags, Users and GlobalSets
     *
     * @param string $handle
     * @return array
     */
    private function _findGlobalRedactorContent(string $handle): array
    {
        $fieldHandle = "content.field_" . $handle;
        $query = (new Query())
            ->addSelect([
                "field" => $fieldHandle,
                "elementId" => "elements.id",
                "type" => "elements.type"
            ])
            ->from(['content' => '{{%content}}'])
            ->innerJoin('{{%elements}} elements', '[[content.elementId]] = [[elements.id]]')
            ->where([
                "not",
                [$fieldHandle => null]
            ])
            ->andWhere([
                "elements.revisionId" => null,
                "elements.dateDeleted" => null
            ]);

        $rows = $query->all();

        foreach ($rows as $key => $row) {
            // eff, neoblocks are installed
            if ($row['type'] === "benf\\neo\\elements\\Block") {
                // just in case the plugin was deleted
                if (!$this->isNeoInstalled) {
                    continue;
                }

                // lets go some digging, if block is deleted, or exist with a revisioned parent
                // remove it from the array of rows to check for refs
                $block = Block::find()
                    ->id($row['elementId'])
                    ->anyStatus()
                    ->one();
                if (!$block || ($block->owner instanceof Entry && $block->owner->getIsRevision())) {
                    unset($rows[$key]);
                }
            }
        }

        $ids = array_merge([], $this->_getIdFromRef($rows));

        return array_unique($ids);
    }

    /**
     * Return the id from the asset ref
     * From {asset:12:url} to 12
     *
     * @param array $rows
     * @return array
     */
    private function _getIdFromRef(array $rows): array
    {
        $ids = [];

        foreach ($rows as $key => $row) {
            if (!StringHelper::contains($row['field'], '{asset')) {
                continue;
            }

            preg_replace_callback(
                '/\{([\w\\\\]+)\:([^@\:\}]+)(?:@([^\:\}]+))?(?:\:([^\}]+))?\}/',
                function ($matches) use (&$ids) {
                    $ids[] = $matches[2];
                },
                $row['field']
            );
        }

        return array_unique($ids);
    }

    private function _getAcceptableParent(ElementInterface $element): ElementInterface
    {
        if (!in_array(get_class($element->owner), $this->acceptableParents)) {
            return $this->_getAcceptableParent($element->owner);
        }
        return $element->owner;
    }

    /**
     * Undocumented function
     *
     * @return Query
     */
    private function _baseFieldsQuery(): Query
    {
        $query = (new Query())
            ->addSelect([
                "handle" => "fields.handle",
                "context" => "fields.context"
            ])
            ->from(['fields' => '{{%fields}}']);

        return $query;
    }
}
