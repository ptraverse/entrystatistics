<?php
namespace Craft;

class Entrystatistics_EntryStatsWidget extends BaseWidget
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $multipleInstances = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Entry Stats');
    }

    /**
     * @inheritDoc ISavableComponentType::getSettingsHtml()
     *
     * @return string
     */
    public function getSettingsHtml()
    {
        return craft()->templates->render('entrystatistics/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    /**
     * @inheritDoc IWidget::getTitle()
     *
     * @return string
     */
    public function getTitle()
    {
        $sectionId = $this->getSettings()->section;

        if (is_numeric($sectionId))
        {
            $section = craft()->sections->getSectionById($sectionId);

            if ($section)
            {
                $title = Craft::t('{section} Entry Statistics', array(
                    'section' => Craft::t($section->name)
                ));
            }
        }

        if (!isset($title))
        {
            $title = Craft::t('Entry Statistics');
        }

        return $title;
    }

    /**
     * @inheritDoc IWidget::getIconPath()
     *
     * @return string
     */
    public function getIconPath()
    {
        return craft()->path->getResourcesPath().'images/widgets/recent-entries.svg';
    }

    /**
     * @inheritDoc IWidget::getBodyHtml()
     *
     * @return string|false
     */
    public function getBodyHtml()
    {
        $statistics = $this->_getStatistics();
        $graph = $this->getSettings()->graph;

        return craft()->templates->render('entrystatistics/body', array(
            'statistics' => $statistics,
            'graph' => $graph
        ));
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseSavableComponentType::defineSettings()
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'section' => array(AttributeType::Mixed, 'default' => '*'),
            'widgetTemplatesPath' => array(AttributeType::String, 'default' => '_widgets/entrystatistics'),
            'graph' => array(AttributeType::Bool, 'default' => 'false'),
        );
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns stats for chosen section, based on the widget settings and user permissions.
     *
     * @return array
     */
    private function _getStatistics()
    {
        $sectionId = $this->getSettings()->section;
        $section = craft()->sections->getSectionById($sectionId)->handle;

        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $section;
        $criteria->limit = null;
        $firstItem = $criteria->first();

        $allData = [];
        if ($this->getSettings()->graph) {
            //Limit the graph to start at 1st of prev month
            $lastMonthStart = date('Y-m-d', strtotime("first day of last month"));
            $lastMonthStartDateWhere = ' >= ' . DateTime::createFromString($lastMonthStart, craft()->timezone)->format(DateTime::MYSQL_DATETIME);
            $criteria->postDate =  $lastMonthStartDateWhere;
            foreach ($criteria as $entry) {
                if (!isset($allData[$entry->postDate->format('Y-m-d')])) {
                    $allData[(string)$entry->postDate->format('Y-m-d')] = 1;
                } else {
                    $allData[(string)$entry->postDate->format('Y-m-d')]++;
                }
            }
        }

        // Today Count
        $today = date('Y-m-d');
        $todayDate = DateTime::createFromString($today, craft()->timezone)->format(DateTime::MYSQL_DATETIME);
        $todayWhere = ' >= ' . $todayDate;
        $criteria->postDate = $todayWhere;
        $todayCount = $criteria->count();

        // Yesterday Count
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $yesterdayCount  = $this->_rangeCount($criteria, $yesterday, $today);

        // This Week (Last Sunday to today) Count
        $thisWeekStart = date('Y-m-d', strtotime("1 sunday ago"));
        $thisWeekCount = $this->_rangeCount($criteria, $thisWeekStart, $today);

        // Last Calendar week Sunday to Sunday Count
        $lastWeekStart = date('Y-m-d', strtotime("2 sundays ago"));
        $lastWeekEnd = date('Y-m-d', strtotime("1 sunday ago"));
        $lastWeekCount  = $this->_rangeCount($criteria, $lastWeekStart, $lastWeekEnd);

        // This Month (1st to today) Count
        $thisMonthStart = date('Y-m-d', strtotime("first day of this month"));
        $thisMonthCount = $this->_rangeCount($criteria, $thisMonthStart, $today);

        //All Time Count
        $firstItemDate = $firstItem->postDate->format('Y-m-d');
        $allTimeCount = $this->_rangeCount($criteria, $firstItemDate, $today);

        return array(
            'counts' => array(
                'today' => $todayCount,
                'yesterday' => $yesterdayCount,
                'this-week' => $thisWeekCount,
                'last-week' => $lastWeekCount,
                'this-month' => $thisMonthCount,
                'all-time' => $allTimeCount,
            ),
            'data' => $allData,
        );

    }

    /**
     * Helper Function for _getStatistics
     *
     */
    private function _rangeCount($criteria, $start, $end) {
        $startDate = DateTime::createFromString($start, craft()->timezone)->format(DateTime::MYSQL_DATETIME);
        $endDate = DateTime::createFromString($end, craft()->timezone)->format(DateTime::MYSQL_DATETIME);
        $where = array(
            'and',
            '>=' . $startDate,
            '<=' . $endDate
        );
        $criteria->postDate = $where;
        $count = $criteria->count();

        return $count;
    }
}
