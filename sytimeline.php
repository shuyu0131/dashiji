<?php
/*
Plugin Name: 大事记时间轴
Version: 1.5
Plugin URL: https://www.emlog.net/plugin/detail/900
Description: 在前台以时间轴的方式展现网站或个人的重大事件，支持双栏布局、可定制主题，支持链接和返回首页功能，支持事件重要程度标记
Author: 属余
Author URL: https://www.emlog.net/author/index/858
*/
!defined('EMLOG_ROOT') && exit('access denied!');

class sytimeline {
    private static $instance;
    private $db;
    private $storage;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->storage = Storage::getInstance('sytimeline');
        // 初始化插件
        $this->initPlugin();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 初始化插件设置
    private function initPlugin() {
        // 首次安装时设置默认值
        if ($this->storage->getValue('is_active') === null) {
            $this->storage->setValue('is_active', 'yes');
            $this->storage->setValue('sytimeline_title', '大事记');
            $this->storage->setValue('sytimeline_subtitle', '记录我们走过的每一步重要历程');
            $this->storage->setValue('sytimeline_color', '#3B82F6');
            $this->storage->setValue('show_home_btn', 'yes');
            
            // 设置默认事件
            $default_events = [
                ['date' => '2018-03-21', 'content' => '第一次接触emlog', 'importance' => 'normal'],
                ['date' => '2024-11-02', 'content' => '开发第一个emlog主题', 'link' => 'https://www.emlog.net', 'importance' => 'important']
            ];
            $this->storage->setValue('events', json_encode($default_events), 'array');
        }
    }
    
    public function init() {
        // 检查插件是否启用
        if (!$this->isActive()) {
            return;
        }
        
        // 注册前台路由
        addAction('route', 'sytimeline_page_router');
        
        // 添加导航菜单项
        addAction('navbar_ext', 'sytimeline_navbar_ext');
        
        // 添加小工具
        addAction('widget_latest_events', 'sytimeline_widget_latest_events');
    }
    
    // 检查插件是否启用
    public function isActive() {
        return $this->storage->getValue('is_active') === 'yes';
    }
    
    // 激活插件
    public function activate() {
        $this->storage->setValue('is_active', 'yes');
        return true;
    }
    
    // 停用插件
    public function deactivate() {
        $this->storage->setValue('is_active', 'no');
        return true;
    }
}

// 前台路由处理
function sytimeline_page_router($route) {
    if ($route == 'plugin' && isset($_GET['plugin']) && $_GET['plugin'] == 'sytimeline') {
        include __DIR__ . '/sytimeline_show.php';
        exit;
    }
}

// 添加导航菜单项
function sytimeline_navbar_ext() {
    echo '<li class="nav-item"><a class="nav-link" href="' . BLOG_URL . '?plugin=sytimeline">大事记</a></li>';
}

// 添加最新事件小工具
function sytimeline_widget_latest_events() {
    $storage = Storage::getInstance('sytimeline');
    $events = json_decode($storage->getValue('events') ?: '[]', true);
    $theme_color = $storage->getValue('sytimeline_color') ?: '#3B82F6';
    
    // 最多显示5个最新事件
    $events = array_slice($events, 0, 5);
    
    if (!empty($events)) {
        echo '<div class="widget-latest-events mb-4" style="--event-color:' . $theme_color . ';">';
        echo '<h4 class="widget-title">最新大事记</h4>';
        echo '<ul class="list-unstyled">';
        
        foreach ($events as $event) {
            $date = date('Y-m-d', strtotime($event['date']));
            $content = htmlspecialchars($event['content']);
            $importance = isset($event['importance']) ? $event['importance'] : 'normal';
            
            // 根据重要程度添加不同的样式
            $importanceClass = '';
            $importanceIcon = '';
            if ($importance == 'important') {
                $importanceClass = 'font-weight-bold';
                $importanceIcon = '<i class="fas fa-star text-warning mr-1" title="重要事件"></i>';
            } elseif ($importance == 'critical') {
                $importanceClass = 'font-weight-bold text-danger';
                $importanceIcon = '<i class="fas fa-exclamation-circle text-danger mr-1" title="关键事件"></i>';
            }
            
            echo '<li class="d-flex align-items-center mb-2 ' . $importanceClass . '">';
            echo '<span class="event-date" style="color:' . $theme_color . ';">' . $date . '</span>';
            
            echo '<span class="ml-2">' . $importanceIcon;
            
            if (isset($event['link']) && !empty($event['link'])) {
                echo '<a href="' . htmlspecialchars($event['link']) . '" target="_blank" class="ml-2" style="color:inherit; text-decoration:none;">' . $content . '</a>';
            } else {
                echo '<span class="ml-2">' . $content . '</span>';
            }
            
            echo '</span></li>';
        }
        
        echo '</ul>';
        echo '<div class="text-right mt-3">';
        echo '<a href="' . BLOG_URL . '?plugin=sytimeline" class="btn btn-sm btn-outline-secondary">查看全部</a>';
        echo '</div>';
        echo '</div>';
        
        // 添加小工具样式
        echo '<style>';
        echo '.widget-latest-events { border-left: 3px solid var(--event-color); padding-left: 15px; }';
        echo '.widget-latest-events .event-date { font-size: 0.85rem; white-space: nowrap; }';
        echo '</style>';
    }
}

// 获取事件数据
function getEvents() {
    $storage = Storage::getInstance('sytimeline');
    return json_decode($storage->getValue('events') ?: '[]', true);
}

// 初始化插件
$sytimeline = sytimeline::getInstance();
$sytimeline->init();

// 插件激活初始化 - 添加条件检查避免函数重复声明
if (!function_exists('callback_init')) {
    function callback_init() {
        $plugin_storage = Storage::getInstance('sytimeline');
        $timeline_data = $plugin_storage->getValue('events');
        
        if (empty($timeline_data)) {
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

// 插件禁用回调 - 添加条件检查避免函数重复声明
if (!function_exists('callback_close')) {
    function callback_close() {
        $plugin_storage = Storage::getInstance('sytimeline');
        // 设置插件禁用状态
        $plugin_storage->setValue('is_active', 'no', 'string');
    }
}

// 插件删除回调 - 添加条件检查避免函数重复声明
if (!function_exists('callback_rm')) {
    function callback_rm() {
        $plugin_storage = Storage::getInstance('sytimeline');
        // 删除所有插件相关数据
        $plugin_storage->deleteAllName('YES');
    }
}

// 插件更新回调 - 添加条件检查避免函数重复声明
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
            
            // 迁移其他设置
            $settings = [
                'timeline_title' => 'sytimeline_title',
                'timeline_subtitle' => 'sytimeline_subtitle',
                'timeline_color' => 'sytimeline_color',
                'show_home_btn' => 'show_home_btn'
            ];
            
            foreach ($settings as $old_key => $new_key) {
                $old_value = $old_storage->getValue($old_key);
                if ($old_value !== null) {
                    $plugin_storage->setValue($new_key, $old_value);
                }
            }
            
            // 更新版本号
            $plugin_storage->setValue('sytimeline_version', '1.5');
            
            // 记录更新日志
            error_log('sytimeline plugin updated to version 1.5');
        }
    }
}

// 检查插件是否启用的函数 - 添加条件检查避免函数重复声明
if (!function_exists('is_sytimeline_active')) {
    function is_sytimeline_active() {
        $plugin_storage = Storage::getInstance('sytimeline');
        return $plugin_storage->getValue('is_active') === 'yes';
    }
}