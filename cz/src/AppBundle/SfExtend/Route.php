<?php

namespace AppBundle\SfExtend;

use Symfony\Component\Routing\Route as BaseRoute;

class Route extends BaseRoute
{
    /**
     * @var string
     */
    private $path = '/';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var array
     */
    private $schemes = array();

    /**
     * @var array
     */
    private $methods = array();

    /**
     * @var array
     */
    private $defaults = array();

    /**
     * @var array
     */
    private $requirements = array();

    /**
     * @var array
     */
    private $options = array();

    /**
     * @var null|CompiledRoute
     */
    private $compiled;

    /**
     * @var array
     */
    private $permissions = array();

    public function __construct($path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array(), $permissions = array())
    {
        parent::__construct($path, $defaults, $requirements, $options, $host, $schemes, $methods);
        $this->setPermissions($permissions);
    }

    public function serialize()
    {
        return serialize(array(
            'path' => $this->path,
            'host' => $this->host,
            'defaults' => $this->defaults,
            'requirements' => $this->requirements,
            'options' => $this->options,
            'schemes' => $this->schemes,
            'methods' => $this->methods,
            'compiled' => $this->compiled,
            'permissions' => $this->permissions,
        ));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->path = $data['path'];
        $this->host = $data['host'];
        $this->defaults = $data['defaults'];
        $this->requirements = $data['requirements'];
        $this->options = $data['options'];
        $this->schemes = $data['schemes'];
        $this->methods = $data['methods'];
        $this->permissions = $data['permissions'];

        if (isset($data['compiled'])) {
            $this->compiled = $data['compiled'];
        }
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $permissions;
    }
}
