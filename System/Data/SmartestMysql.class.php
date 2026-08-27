<?php

/**
 * Implements a MySQL abstraction layer.
 *
 * This class intentionally keeps the legacy SmartestMysql API while using PDO
 * internally so older callers can be migrated gradually.
 */

if(!defined("UNSUPPORTED_QUERY")){
    define("UNSUPPORTED_QUERY", false);
}

require SM_ROOT_DIR.'System/Base/Exceptions/SmartestDatabaseException.class.php';

class SmartestMysql{

    protected $dblink;
    public $lastQuery;
    protected $connection_config;
    protected $cachedQueryHistory = array();
    protected $queryHistory = array();
    protected $id;
    protected $databaseName;
    protected $options = array();
    protected $queryHashes = array();
    protected $retrievalQueryTypes = array('SELECT', 'SHOW');
    protected $log = array();
    protected $lastErrorCode = 0;
    protected $lastErrorMessage = '';
    protected $lastAffectedRows = 0;
    protected $r;
    protected static $defaultInstance;
    protected static $loggingRawQueryUsage = false;
    private $_request_id;

    public function __construct(SmartestParameterHolder $dbconfig){

        $this->connection_config = $dbconfig;
        $this->r = SmartestInfo::$revision;
        $this->_request_id = SmartestStringHelper::random(8);
        $this->queryHistory = array();

        if($this->connect()){
            self::$defaultInstance = $this;
            $this->lastQuery = "No queries made yet.";
        }
    }

    public function __destruct(){
        $this->clearQueryHistoryCache();
    }

    protected function connect(){

        $host = $this->connection_config['host'];
        $database = $this->connection_config['database'];
        $username = $this->connection_config['username'];
        $password = $this->connection_config['password'];

        $dsn = 'mysql:host='.$host.';dbname='.$database.';charset=utf8mb4';

        try{
            $this->dblink = new PDO($dsn, $username, $password, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ));

            $this->databaseName = $database;
            $this->dblink->exec("SET NAMES 'utf8mb4'");
            return true;

        }catch(PDOException $e){

            if($this->connection_config['short_name']){
                SmartestCache::clear('dbc_'.$this->connection_config['short_name'], true);
            }

            $exception = new SmartestDatabaseException("Could not connect to MySQL. MySQL says: ".$e->getMessage().".", SmartestDatabaseException::CONNECTION_IMPOSSIBLE);
            $exception->setUsername($username);
            $exception->setHost($host);
            $exception->setDatabase($database);
            throw $exception;

        }

    }

    protected function reconnect(){
        return $this->connect();
    }

    protected function ensureConnection(){

        if(!$this->dblink && !$this->reconnect()){
            if($this->connection_config['short_name']){
                SmartestCache::clear('dbc_'.$this->connection_config['short_name'], true);
            }

            throw new SmartestDatabaseException("Lost connection to to MySQL database and could not reconnect", SmartestDatabaseException::LOST_CONNECTION);
        }

    }

    public function getConnectionName(){
        return $this->connection_config['short_name'];
    }

    public function getTables($refresh=false){

        $sql = "SHOW TABLES FROM `".$this->connection_config['database'].'`';
        $tables = $this->queryToArray($sql, $refresh);
        $table_names = array();

        foreach($tables as $tr){
            $table_names[] = $tr["Tables_in_".$this->connection_config['database']];
        }

        return $table_names;

    }

    public function getColumns($table, $refresh=false){

        $sql = "SHOW COLUMNS FROM `".$table.'`';
        $columns = $this->queryToArray($sql, $refresh);
        return $columns;

    }

    public function getColumnNames($table, $refresh=false){

        $sql = "SHOW COLUMNS FROM `".$table."`";
        $columns = $this->queryToArray($sql, $refresh);
        $names = array();

        foreach($columns as $column){
            $names[] = $column['Field'];
        }

        return $names;

    }

    public function rawQuery($querystring){

        $this->ensureConnection();
        $this->logRawQueryUsage($querystring);

        try{
            $statement = $this->dblink->query($querystring);
            $this->id = $this->dblink->lastInsertId();
            $this->lastAffectedRows = ($statement instanceof PDOStatement) ? $statement->rowCount() : 0;
            $this->clearLastError();
            $this->recordLiveQuery($querystring);
            return $statement;
        }catch(PDOException $e){
            $this->rememberError($e);
            $this->recordLiveQuery($querystring);
            return false;
        }

    }

    public function prepareQuery($querystring, $params=array()){

        $this->ensureConnection();

        try{
            $statement = $this->dblink->prepare($querystring);
            $statement->execute($params);
            $this->id = $this->dblink->lastInsertId();
            $this->lastAffectedRows = $statement->rowCount();
            $this->clearLastError();
            $this->recordLiveQuery($this->formatPreparedQueryForLog($querystring, $params));

            switch($this->getQueryType($querystring)){

                case 'SELECT':
                case 'SHOW':
                    return $statement->fetchAll(PDO::FETCH_ASSOC);

                case 'INSERT':
                    return $this->id;

                case 'UPDATE':
                case 'DELETE':
                    return $this->lastAffectedRows;

                default:
                    return $statement;
            }

        }catch(PDOException $e){
            $this->rememberError($e);
            $this->recordLiveQuery($this->formatPreparedQueryForLog($querystring, $params));
            return false;
        }

    }

    public function preparedQuery($querystring, $params=array()){

        return $this->prepareQuery($querystring, $params);

    }

    public function escape($value){

        $this->ensureConnection();

        if(is_null($value)){
            return null;
        }

        $quoted = $this->dblink->quote((string) $value);

        if($quoted === false){
            return addslashes((string) $value);
        }

        return substr($quoted, 1, -1);

    }

    public static function escapeString($value){

        if(self::$defaultInstance instanceof self){
            return self::$defaultInstance->escape($value);
        }

        return addslashes((string) $value);

    }

    protected function getInsertId(){
        return $this->id;
    }

    public function howMany($querystring, $file='', $line=''){

        $this->ensureConnection();

        try{
            $statement = $this->dblink->query($querystring);
            $this->lastAffectedRows = ($statement instanceof PDOStatement) ? $statement->rowCount() : 0;
            $this->clearLastError();
            $this->recordLiveQuery($querystring);

            if($statement instanceof PDOStatement){
                return count($statement->fetchAll(PDO::FETCH_ASSOC));
            }

            return 0;
        }catch(PDOException $e){
            $this->rememberError($e);
            $this->recordLiveQuery($querystring);
            return 0;
        }

    }

    protected function getHashFromQuery($query){
        $hash = sha1($query);
        return $hash;
    }

    public function clearQueryFromCache($query){

        $hash = $this->getHashFromQuery($query);

        if(isset($this->queryHashes[$hash])){
            unset($this->queryHashes[$hash]);
        }

        $cache_name = 'SMCR'.$this->_request_id.$hash;
        return SmartestCache::clear($cache_name, true);

    }

    public function queryToArray($querystring, $refresh=false){

        $this->ensureConnection();

        if($this->queryReturnsData($querystring)){
            $result = $this->getSelectQueryResult($querystring, $refresh);
            return $result;
        }else{
            throw new SmartestException("Unsupported '".$this->getQueryType($querystring)."' query type in query '".$querystring."' SmartestMysql::queryToArray(). Use SmartestMysql::rawQuery()", SM_ERROR_DB);
        }

    }

    public function queryFieldsToArrays($fields, $query, $refresh=false){

        $result = $this->queryToArray($query, $refresh);

        if(count($result)){

            $result_fields = array_keys($result[0]);

            foreach($fields as $f){
                if(!in_array($f, $result_fields)){
                    throw new SmartestDatabaseException("SmartestMysql::queryFieldsToArrays() Requested field '".$f."' not found in returned fieldset: ".implode(', ', $result_fields), SmartestDatabaseException::LOST_CONNECTION);
                }
            }

            $return_data = array();
            $i = 0;

            foreach($result as $record){
                foreach($fields as $f){
                    $return_data[$f][$i] = $record[$f];
                }
                ++$i;
            }

            return $return_data;

        }else{

            $r = array();

            foreach($fields as $f){
                $r[$f] = array();
            }

            return $r;

        }

    }

    protected function getSelectQueryResult($query, $refresh=false){

        $hash = $this->getHashFromQuery($query);

        if(isset($this->queryHashes[$hash]) && !$refresh){

            return $this->loadQueryDataFromCache($query);

        }else{

            try{
                $statement = $this->dblink->query($query);
                $resultArray = ($statement instanceof PDOStatement) ? $statement->fetchAll(PDO::FETCH_ASSOC) : array();
                $this->lastAffectedRows = ($statement instanceof PDOStatement) ? $statement->rowCount() : 0;
                $this->clearLastError();
                $this->recordLiveQuery($query);

                $this->queryHashes[$hash] = 1;
                $this->saveQueryDataToCache($query, $resultArray);

                return $resultArray;
            }catch(PDOException $e){
                $this->rememberError($e);
                $this->recordLiveQuery($query);
                return array();
            }

        }

    }

    protected function queryReturnsData($querystring){
        $qstart = strtoupper(substr(ltrim($querystring), 0, 4));
        return ($qstart == 'SELE' || $qstart == 'SHOW');
    }

    protected function loadQueryDataFromCache($query){

        $hash = $this->getHashFromQuery($query);
        $cache_name = 'SMCR'.$this->_request_id.$hash;

        if(SmartestCache::hasData($cache_name, true)){
            $result = SmartestCache::load($cache_name, true);
            $this->recordCachedQuery($query);
            return $result;
        }else{
            return $this->getSelectQueryResult($query, true);
        }
    }

    protected function saveQueryDataToCache($query, $data){

        $hash = $this->getHashFromQuery($query);

        if(is_array($data)){
            $cache_name = 'SMCR'.$this->_request_id.$hash;
            SmartestCache::save($cache_name, $data, -1, true);
        }else{
            throw new SmartestDatabaseException("SmartestMysql::saveQueryDataToCache() expects array.", SmartestDatabaseException::INVALID_CACHE_DATA);
        }

    }

    public function clearQueryHistoryCache(){

        foreach($this->queryHashes as $hash=>$bin){
            $cache_name = 'SMCR'.$this->_request_id.$hash;
            SmartestCache::clear($cache_name, true);
        }

    }

    public function specificQuery($wantedField, $qualifyingField, $qualifyingValue, $table){

        $this->ensureConnection();

        $query = "SELECT $wantedField, $qualifyingField FROM $table WHERE $qualifyingField='".$this->escape($qualifyingValue)."' LIMIT 1";

        try{
            $statement = $this->dblink->query($query);
            $this->lastAffectedRows = ($statement instanceof PDOStatement) ? $statement->rowCount() : 0;
            $this->clearLastError();
            $this->recordLiveQuery($query);

            if($statement instanceof PDOStatement){
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return is_array($row) && array_key_exists($wantedField, $row) ? $row[$wantedField] : null;
            }
        }catch(PDOException $e){
            $this->rememberError($e);
            $this->recordLiveQuery($query);
        }
    }

    protected function getQueryType($querystring){

        $bits = preg_split('/\s+/', ltrim($querystring));
        if(isset($bits[0])){
            $first = $bits[0];
            return strtoupper($first);
        }else{
            return false;
        }
    }

    public function query($querystring, $file='', $line='') {
        switch ($this->getQueryType($querystring)){

            case 'UPDATE':
            case 'DELETE':
                if($this->rawQuery($querystring)){
                    return $this->lastAffectedRows;
                }

                break;

            case 'INSERT':
                $this->rawQuery($querystring);
                return $this->id;

                break;

            case 'SELECT':
            case 'SHOW':
                if($data = $this->queryToArray($querystring)){
                    return $data;
                }else{
                    return false;
                }

                break;

            default:
                return UNSUPPORTED_QUERY;
        }

    }

    protected function rememberError(PDOException $e){
        $this->lastErrorCode = $e->getCode();
        $this->lastErrorMessage = $e->getMessage();
    }

    protected function clearLastError(){
        $this->lastErrorCode = 0;
        $this->lastErrorMessage = '';
    }

    protected function logRawQueryUsage($querystring){

        if(self::$loggingRawQueryUsage){
            return;
        }

        self::$loggingRawQueryUsage = true;

        try{
            $caller = $this->findExternalQueryCaller();
            $query = $this->redactQueryForLog($querystring);

            if(strlen($query) > 500){
                $query = substr($query, 0, 500).'...';
            }

            $message = 'SmartestMysql::rawQuery() used';

            if($caller){
                $message .= ' at '.$caller['file'].':'.$caller['line'];
            }

            $message .= '. Query hash: '.$this->getHashFromQuery($querystring).'. Query preview: '.$query;
            SmartestLog::getInstance('database_raw_query')->log($message, SmartestLog::NOTICE, -1);

        }catch(Throwable $e){
            error_log(date('Y-m-d H:i:s').': SmartestMysql::rawQuery() logging failed: '.$e->getMessage()."\n", 3, SM_ROOT_DIR.'System/Logs/database_raw_query_fallback.log');
        }

        self::$loggingRawQueryUsage = false;

    }

    protected function findExternalQueryCaller(){

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach($trace as $event){
            if(!isset($event['file']) || !isset($event['line'])){
                continue;
            }

            if($event['file'] == __FILE__){
                continue;
            }

            return array('file' => $event['file'], 'line' => $event['line']);
        }

        return null;

    }

    protected function formatPreparedQueryForLog($querystring, $params){

        if(empty($params)){
            return $querystring;
        }

        return $querystring.'; params='.json_encode($params);

    }

    protected function redactQueryForLog($querystring){

        $query = preg_replace('/\s+/', ' ', trim($querystring));
        $query = preg_replace("/'([^'\\\\]|\\\\.)*'/", "'?'", $query);
        $query = preg_replace('/"([^"\\\\]|\\\\.)*"/', '"?"', $query);

        if(strlen($query) > 500){
            $query = substr($query, 0, 500).'...';
        }

        return $query;

    }

    protected function recordLiveQuery($querystring){

        if(strlen($this->lastErrorMessage) > 0){

            $errno = $this->lastErrorCode;
            $error = "MySQL ERROR ".$this->lastErrorCode.": ".$this->lastErrorMessage;

            if(defined('SM_DEVELOPER_MODE') && constant('SM_DEVELOPER_MODE')){

                $e = new SmartestDatabaseException('MySQL ERROR: '.$this->lastErrorCode.": ".$this->lastErrorMessage, SmartestDatabaseException::UNKNOWN_TYPE);

                foreach($e->getTrace() as $event){
                    if($event['function'] == 'queryToArray' || $event['function'] == 'rawQuery'){
                        $e->setMessage($e->getMessage().'. Query: <code>'.$querystring.'</code> in '.$event['file'].' on line '.$event['line'].'.');
                        $e->setQuery($querystring);
                        $e->setClientErrorMessage($error);
                        $e->setClientErrorId($errno);
                        break;
                    }

                }

                throw $e;

            }

        }else{

            $error = "Query OK";

        }

        $this->lastQuery = $querystring;
        $this->queryHistory[] = $querystring."; ".$error;

    }

    protected function recordCachedQuery($querystring){

        $this->cachedQueryHistory[] = $querystring;

    }

    public function executeSqlFile($full_file_path){
        if(SmartestFileSystemHelper::isSafeFileName($full_file_path, SM_ROOT_DIR.'System/Install/SqlScripts/')){

            $sql = str_replace("\n", '', file_get_contents($full_file_path));
            preg_match_all('/(CREATE|SHOW|DROP|DELETE|SELECT|INSERT|UPDATE|ALTER|GRANT)\s.+?;/i', $sql, $matches, PREG_PATTERN_ORDER);
            $queries = $matches[0];

            foreach($queries as $q){
                try{
                    $result = $this->rawQuery($q);
                }catch(SmartestDatabaseException $e){
                    if($this->queryIsIgnorableSchemaReplayError($q, $e)){
                        continue;
                    }

                    throw $e;
                }

                if($result === false && $this->lastErrorIsIgnorableSchemaReplayError($q)){
                    continue;
                }
            }

        }else{
            throw new SmartestDatabaseException("The file ".$full_file_path." is outside the permitted storage area for SQL files: ".SM_ROOT_DIR.'System/Install/SqlScripts/', SmartestDatabaseException::INVALID_SQL_FILE_STORAGE_DIR);
        }
    }

    protected function queryIsIgnorableSchemaReplayError($querystring, SmartestDatabaseException $e){

        return $this->isCreateTableQuery($querystring) && strpos($e->getClientErrorMessage(), 'Base table or view already exists') !== false;

    }

    protected function lastErrorIsIgnorableSchemaReplayError($querystring){

        return $this->isCreateTableQuery($querystring) && strpos($this->lastErrorMessage, 'Base table or view already exists') !== false;

    }

    protected function isCreateTableQuery($querystring){

        return (bool) preg_match('/^\s*CREATE\s+TABLE\s+/i', $querystring);

    }

    public function getLastQuery(){
        return $this->lastQuery;
    }

    public function getDebugInfo(){
        return array('live'=>$this->queryHistory, 'cached'=>$this->cachedQueryHistory);
    }

}
