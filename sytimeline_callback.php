<?php
!defined('EMLOG_ROOT') && exit('access denied!');

// 插件激活初始化
if (!function_exists('callback_init')) {
    function callback_init() {
        $plugin_storage = Storage::getInstance('sytimeline');
        $sytimeline_data = $plugin_storage->getValue('events');
        
        if (empty($sytimeline_data)) {
            $default_events = [
                ['date' => '2018-03-21', 'content' => '第一次接触emlog', 'importance' => 'normal'],
                ['date' => '2024-11-02', 'content' => '开发第一个emlog主题', 'link' => 'https://www.emlog.net', 'importance' => 'important']
            ];
            $plugin_storage->setValue('events', json_encode($default_events), 'array');
        }
        // 设置插件启用状态
        $plugin_storage->setValue('is_active', 'yes', 'string');
        
        // 设置默认显示设置
        $plugin_storage->setValue('sytimeline_title', $plugin_storage->getValue('sytimeline_title') ?: '大事记', 'string');
        $plugin_storage->setValue('sytimeline_subtitle', $plugin_storage->getValue('sytimeline_subtitle') ?: '记录我们走过的每一步重要历程', 'string');
        $plugin_storage->setValue('sytimeline_color', $plugin_storage->getValue('sytimeline_color') ?: '#3B82F6', 'string');
        $plugin_storage->setValue('show_home_btn', $plugin_storage->getValue('show_home_btn') ?: 'yes', 'string');
    }
}

// 插件禁用回调
if (!function_exists('callback_close')) {
    function callback_close() {
        $plugin_storage = Storage::getInstance('sytimeline');
        // 设置插件禁用状态
        $plugin_storage->setValue('is_active', 'no', 'string');
    }
}

// 插件删除回调
if (!function_exists('callback_rm')) {
    function callback_rm() {
        $plugin_storage = Storage::getInstance('sytimeline');
        // 删除所有插件相关数据
        $plugin_storage->deleteAllName('YES');
    }
}

// 插件更新回调
if (!function_exists('callback_up')) {
    function callback_up() {
        $plugin_storage = Storage::getInstance('sytimeline');
        
        // 检查并执行版本兼容性更新
        $current_version = $plugin_storage->getValue('sytimeline_version');
        
        // 如果没有版本记录，或版本低于当前版本，执行更新
        if ($current_version === null || version_compare($current_version, '1.5', '<')) {
            // 数据迁移：从旧版timeline迁移数据
            $old_storage = Storage::getInstance('timeline');
            $old_events = $old_storage->getValue('events');
            if (!empty($old_events)) {
                $events = json_decode($old_events, true);
                // 为旧事件添加重要程度字段
                $updated_events = array_map(function($event) {
                    if (!isset($event['importance'])) {
                        $event['importance'] = 'normal';
                    }
                    if (!isset($event['link'])) {
                        $event['link'] = '';
                    }
                    return $event;
                }, $events);
                
                $plugin_storage->setValue('events', json_encode($updated_events), 'array');
            } else {
                // 如果没有旧数据，则更新当前数据
                $events = json_decode($plugin_storage->getValue('events') ?: '[]', true);
                $updated_events = array_map(function($event) {
                    if (!isset($event['importance'])) {
                        $event['importance'] = 'normal';
                    }
                    if (!isset($event['link'])) {
                        $event['link'] = '';
                    }
                    return $event;
                }, $events);
                
                $plugin_storage->setValue('events', json_encode($updated_events), 'array');
            }
            
            // 更新版本号
            $plugin_storage->setValue('sytimeline_version', '1.5');
            
            // 记录更新日志
            error_log('sytimeline plugin updated to version 1.5');
        }
    }
}

// 检查插件是否启用的函数
if (!function_exists('is_sytimeline_active')) {
    function is_sytimeline_active() {
        $plugin_storage = Storage::getInstance('sytimeline');
        return $plugin_storage->getValue('is_active') === 'yes';
    }
}