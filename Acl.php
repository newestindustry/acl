<?php

namespace Nicci\Acl;

use \Nicci\Acl\Role;

/**
 * Acl class.
 */
class Acl {

    /**
     * rawConfig
     * 
     * (default value: array())
     * 
     * @var array
     * @access private
     */
    private $rawConfig = array();
    
    /**
     * roles
     * 
     * (default value: array())
     * 
     * @var array
     * @access private
     */
    private $roles = array();
    
    /**
     * resources
     * 
     * (default value: array())
     * 
     * @var array
     * @access private
     */
    private $resources = array();
    
    /**
     * access
     * 
     * (default value: array())
     * 
     * @var array
     * @access private
     */
    private $access = array();

    /**
     * __construct function.
     * 
     * @access public
     * @param string $config (default: "")
     * @return void
     */
    public function __construct($config = "") {
        if(is_string($config) && $config !== "") {
            $this->readConfig($config);
        }
    }
    
    /**
     * readConfig function.
     * 
     * @access public
     * @param string $config (default: "")
     * @return array
     */
    public function readConfig($filename = "") {
        if(!is_string($filename)) {
            throw new \Nicci\Acl\Exception(sprintf("No config file given."), 10001);
        }

        if($filename !== "") {
            if(!file_exists($filename)) {
                throw new \Nicci\Acl\Exception(sprintf('Config file "%s" not found.', $filename), 10002);
            }
            
            $this->rawConfig = @yaml_parse_file($filename, 0);
            if(!$this->rawConfig) {
                throw new \Nicci\Acl\Exception(sprintf('Config file "%s" failed to parse.', $filename), 10002);
            }
            $this->parseRoles();
            $this->parseRecursiveResources($this->rawConfig['resources']);
            $this->parseAccessFromResources();
        }
        

        
        return $this->rawConfig;
    }
    
    /**
     * parseRoles function.
     * 
     * @access private
     * @return void
     */
    private function parseRoles() {
        if(isset($this->rawConfig['roles'])) {
            foreach($this->rawConfig['roles'] as $role) {
                $this->roles[] = $role;
            }
        }
    }

    /**
     * parseAccessFromResources function.
     * 
     * @access private
     * @return void
     */
    private function parseAccessFromResources() {
        foreach($this->resources as $resource => $access) {
            foreach($access as $role) {
                if(!isset($this->access[$role])) {
                    $this->access[$role] = array();
                }
                
                if(!in_array($resource, $this->access[$role])) {
                    $this->access[$role][] = $resource;
                }
            }
        }
    }
    
    /**
     * parseRecursiveResources function.
     * 
     * @access private
     * @param mixed $arr
     * @param array $narr (default: array())
     * @param array $access (default: array())
     * @param string $nkey (default: '')
     * @return array
     */
    private function parseRecursiveResources($arr, $narr = array(), $access = array(), $nkey = '') {
        foreach ($arr as $key => $value) {
            $this->resources[$nkey . $key] = array();
            
            if (isset($value['access']) && is_array($value['access'])) {
                foreach($value['access'] as $acc) {
                    if(!in_array($acc, $access)) {
                        $access[] = $acc;
                    }
                }
            }
            
            $this->resources[$nkey . $key] = $access;

            
            if (isset($value['childs']) && is_array($value['childs'])) {
                $narr = array_merge($narr, $this->parseRecursiveResources($value['childs'], $narr, $access, $nkey . $key . '.'));
            } else {
                $narr[$nkey . $key] = $value;
            }
        }       
        
        return $arr;
    }
    
    /**
     * checkIfRoleExists function.
     * 
     * @access private
     * @param Role $role
     * @return boolean
     */
    private function checkIfRoleExists($role) {
        return in_array($role, $this->roles, true);
    }
    
    /**
     * checkIfResourceExists function.
     * 
     * @access private
     * @param mixed $resource
     * @return boolean
     */
    private function checkIfResourceExists($resource) {
        if(isset($this->resources[$resource])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * checkIfRoleHasAccessToResource function.
     * 
     * @access private
     * @param mixed $role
     * @param mixed $resource
     * @return boolean
     */
    private function checkIfRoleHasAccessToResource($role, $resource) {
        if(!isset($this->access[$role])) {
            return false;
        }
        return in_array($resource, $this->access[$role], true);
    }
        
    /**
     * check function.
     * 
     * @access public
     * @param Role $role
     * @param Resource $resource
     * @return boolean
     */
    public function check($role, $resource) {
        if(!$this->checkIfRoleExists($role)) {
            return false;
        }
        
        if(!$this->checkIfResourceExists($resource)) {
            return false;
        }
        
        if(!$this->checkIfRoleHasAccessToResource($role, $resource)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * enforce function.
     * 
     * @access public
     * @param mixed $role
     * @param mixed $resource
     * @return boolean
     */
    public function enforce($role, $resource) {
        if(!$this->checkIfRoleExists($role)) {
            throw new \Nicci\Acl\Exception(sprintf('Role "%s" not found', $role), 11001);
        }
        
        if(!$this->checkIfResourceExists($resource)) {
            throw new \Nicci\Acl\Exception(sprintf('Resource "%s" not found', $resource), 11002);
        }
        
        if(!$this->checkIfRoleHasAccessToResource($role, $resource)) {
            throw new \Nicci\Acl\Exception(sprintf('Role "%s" has no access to resource "%s".', $role, $resource), 11003);
        }
        
        return true;
    }
    
    /**
     * getRoles function.
     * 
     * @access public
     * @return array
     */
    public function getRoles() {
        return $this->roles;
    }
    
    /**
     * getResources function.
     * 
     * @access public
     * @return array
     */
    public function getResources() {
        return $this->resources;
    }
    
    /**
     * getAccess function.
     * 
     * @access public
     * @return array
     */
    public function getAccess() {
        return $this->access;
    }
}
