<?php
namespace Jarrus90\AdvancedCleanup\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class CronRunner {

    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;
    private DateTime $dateTime;
    private DateTime\DateTime $date;
    private ResourceConnection $resourceConnection;

    protected array $tables = [
        'report_event',
        'report_viewed_product_index',
        'report_compared_product_index',
        'customer_visitor',
    ];

    public function __construct(
        LoggerInterface      $logger,
        ScopeConfigInterface $scopeConfig,
        DateTime             $dateTime,
        DateTime\DateTime    $time,
        ResourceConnection   $resourceConnection,
    ) {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->date = $time;
    }

    public function execute(): void {
        foreach($this->tables AS $table) {
            $this->processTable($table);
            $this->logger->info($table .' cleaned up.');
        }
    }

    protected function processTable(string $table): void {
        if(!$this->scopeConfig->getValue("advancedcleanup/{$table}/active", ScopeInterface::SCOPE_WEBSITES)) {
            return;
        }
        $keep = 3600 * 24 * (int)$this->scopeConfig->getValue("advancedcleanup/{$table}/keep_days", ScopeInterface::SCOPE_WEBSITES);
        $connection = $this->resourceConnection->getConnection();
        $maxBulkStartTime = $this->dateTime->formatDate($this->date->gmtTimestamp() - $keep);
        $connection->delete($table, ['start_time <= ?' => $maxBulkStartTime]);
    }
}
