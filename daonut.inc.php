<?php
/**
 * @author Karen Ziv <karen@perlessence.com>
 **/
class DAOFactory {

    const DIR_CONNECTORS = '/connectors/';
    const DIR_RESOURCES  = '/resources/';
    protected static $connectors = array();
    protected static $resources = array();
    
    public static $test_factory;  // This is a unit-testing hook. Do NOT use it for anything else    
        
    /**
     * Creates a connector object
     * Looks for an existing connector instance matching the given connection string. If
     * one does not exist, loads the correct scheme class and stores an instance of the
     * connector. This allows the
     * @param  {str}  Connection string in format scheme://user:pass@host:port/(db | path )
     * @return {bool} Success or failure
     **/
    public static function connect($connection_string) {

        $connection_string = trim($connection_string);
        if (empty($connection_string)) {
            return FALSE;
        }

        // If an existing version of this connector exists, no need to recreate
        if (isset(self::$connectors[$connection_string])) {
            return TRUE;
        }

        // Create and store a new connector
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        if (empty($scheme)) {
            return FALSE;
        }

        // Unit testing static method
        $connector = self::getConnector($connection_string);        
        if (!$connector) {
            return FALSE;
        }
        self::$connectors[$connection_string] = $connector;
        
        return TRUE;
    }

    public static function getConnector($connection_string) {

        if (empty($connection_string)) {
            return FALSE;
        }
        
        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, 'getConnector')) {
            $f = self::$test_factory;
            return $f->getConnector($connection_string);
        }
        
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        $class_name = 'Connector_' . $scheme;
        if (!class_exists($class_name)) {
            $connector_path = dirname(__FILE__) . self::DIR_CONNECTORS . $scheme . '.connector.php';
            @include $connector_path;
            if (!class_exists($class_name)) {
                error_log("Connector '" . $class_name . "' not found in '$connector_path'");
                return FALSE;
            }
        }
        return new $class_name($connection_string);
    }

    /**
     * Creates a connection to a data resource
     * @param {str} daonut resource string in format DB.Table
     * @param {str} Query type (default: 'select')
     **/
    public static function create($resource, $type = 'select') {
        
        $resource = trim($resource);
        if (empty($resource)) {
            error_log("No table resource defined.");
            return FALSE;
        }

        // Make sure query is of valid type
        $allowed_query_types = array('select', 'update', 'insert', 'delete','info');
        $type = strtolower($type);
        if (!in_array($type, $allowed_query_types)) {
            error_log("Invalid query type '" . $type . "' for resource '" . $resource . "'");
            return FALSE;
        }

        $resource = strtolower($resource);
        // If a data resource file exists, load its info
        $class_name = 'DAO_' . str_replace('.', '_', $resource); 
        if (!class_exists($class_name)) {
            @include dirname(__FILE__) . self::DIR_RESOURCES . str_replace('.', DIRECTORY_SEPARATOR, $resource) . '.dao.php';
        }
        if (class_exists($class_name)) {
            $dao = new $class_name();
            if (! $dao instanceof DynamicDao) {
                error_log("Resource '" . $resource . "' is not a valid DynamicDao");
                return FALSE;
            }
        }
        else {
            $dao = new DynamicDao();
        }

        // Create a new connection if one doesn't exist

    }
}

class DynamicDao {

}
