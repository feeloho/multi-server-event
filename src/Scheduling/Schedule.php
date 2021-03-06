<?php
namespace Feeloho\MultiServerEvent\Scheduling;

use Illuminate\Console\Scheduling\CacheMutex;
use Illuminate\Console\Scheduling\Schedule as NativeSchedule;

class Schedule extends NativeSchedule
{

    /**
     * 添加新的命令
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' '.$this->compileParameters($parameters);
        }
        $this->events[] = $event = new Event(app()->make(CacheMutex::class), $command);
        return $event;
    }
}