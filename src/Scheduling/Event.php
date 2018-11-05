<?php
namespace Feeloho\MultiServerEvent\Scheduling;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event as NativeEvent;
use Illuminate\Console\Scheduling\CacheMutex;
use Feeloho\MultiServerEvent\Events\EnsureCleanUpExecuted;

class Event extends NativeEvent
{
    /**
     * mutex前缀
     * @var string
     */
    protected $prefix = 'event:command:';

    /**
     * hash标识服务器锁定进程
     * @var string
     */
    protected $key;

    public $eventLost = false;

    /**
     * 创建事件实例
     *
     * @param Mutex $cacheMutex
     * @param  string $command
     * @throws \RuntimeException
     */
    public function __construct(CacheMutex $cacheMutex, $command)
    {
        parent::__construct($cacheMutex, $command);
        $this->key = $this->getKey();
        $this->then(function() {
            $this->clearMultiserver();
        });
    }
    /**
     * 创建多服务器中命令互斥锁
     * at the same time.
     * @return $this
     */
    public function withoutOverlappingMultiServer()
    {
        return $this->skip(function() {
            return $this->skipMultiserver();
        });
    }

    /**
     * 设置命令锁
     * @return boolean true if we want to skip
     */
    public function skipMultiserver()
    {
        $this->eventLost = true;
        try {
            $this->cache->forever($this->key, Carbon::now());
        } catch (\Exception $e) {
            $this->eventLost = false;
        }
        return $this->eventLost === false;
    }

    /**
     * @return string
     */
    private function getKey()
    {
        return $this->prefix . md5($this->expression . $this->command);
    }

    /**
     * 无法关闭cron进程时，防止此命令永远执行，执行命令最长时间
     * @param integer $minutes
     * @return $this
     */
    public function ensureFinishedMultiServer($minutes)
    {
        return $this->when(function() use ($minutes) {
            return $this->ensureFinished($minutes);
        });
    }

    /**
     * 清理命令
     * @param integer $minutes
     * @return boolean true if we want to skip
     * @throws \RuntimeException
     */
    public function ensureFinished($minutes)
    {
        if ($this->cache->has($this->key) &&
            $this->cache->get($this->key) < Carbon::now()->subMinutes($minutes) &&
            $this->clearMultiserver()
        ) {
            event(new EnsureCleanUpExecuted($this->command));
        }
        return true;
    }

    /**
     * 清理服务器锁
     * @return boolean
     */
    public function clearMultiserver()
    {
        if ($this->key) {
            $this->cache->forget($this->key);
        }
        return true;
    }
}