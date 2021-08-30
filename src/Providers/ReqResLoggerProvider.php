<?php

namespace IteLog\Providers;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use IteLog\Facades\IteLogFacades;
use IteLog\Service\IteLogService;

/**
 * Class ReqResLoggerProvider
 * @package IteLog\Providers
 */
class ReqResLoggerProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $result = [];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('iteLog', function () {
            return new IteLogService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setSql();
    }

    /**
     * sql语句调试
     */
    public function setSql()
    {
        $logger = config('itelog.logger');
        if ($logger) {
            DB::listen(function ($query) {
                $sql = str_replace('?', '"' . '%s' . '"', $query->sql);
                $qBindings = [];
                foreach ($query->bindings as $key => $value) {
                    if (is_numeric($key)) {
                        $qBindings[] = $value;
                    } else {
                        $sql = str_replace(':' . $key, '"' . $value . '"', $sql);
                    }
                }
                if (!empty($qBindings)) {
                    $sql = vsprintf($sql, $qBindings);
                }
                $sql = str_replace("\\", "", $sql);
                //
                list($t1, $t2) = explode(' ', microtime());
                $microtime = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
                $this->result[] = [
                    'startTime' => date('Y-m-d H:i:s.u', $microtime),
                    'executionTime' => $query->time . 'ms;',
                    'sql' => $sql,
                ];
                //设置数据
                IteLogFacades::setSqlList($this->result);
            });
        }
    }
}
