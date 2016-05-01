<?php
namespace Minhbang\Status;
/**
 * Class Status
 *
 * @package Minhbang\Status
 */
class Status
{
    /**
     * Danh sách các status managers
     *
     * @var array
     */
    protected $managers = [];

    /**
     * @param string $name Resource class name
     *
     * @return StatusManager
     */
    public function of($name)
    {
        abort_unless(isset($this->managers[$name]), 500, sprintf("Unregistered '%s' status manager!", $name));
        if (is_string($this->managers[$name])) {
            abort_unless(class_exists($this->managers[$name]), 500, sprintf("Class '%s' not found!", $this->managers[$name]));
            $this->managers[$name] = new $this->managers[$name]();
        }

        return $this->managers[$name];
    }

    /**
     * Đăng ký một status manager cho resource class $name
     * Thực hiện trong service provider của Resource có sử dụng Status
     *
     * @param string $name Resource class name
     * @param string $manager Status manager class name
     */
    public function register($name, $manager)
    {
        $this->managers[$name] = $manager;
    }
}