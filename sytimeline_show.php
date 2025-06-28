<?php 
!defined('EMLOG_ROOT') && exit('access denied!'); 

// 检查插件是否启用
$plugin_storage = Storage::getInstance('sytimeline');
if ($plugin_storage->getValue('is_active') !== 'yes') {
    header('HTTP/1.0 403 Forbidden');
    exit('插件未启用');
}

// 正确获取站点标题
$blogname = Option::get('blogname');

// 获取设置
$timeline_title = $plugin_storage->getValue('sytimeline_title') ?: '大事记';
$timeline_subtitle = $plugin_storage->getValue('sytimeline_subtitle') ?: '记录我们走过的每一步重要历程';
$timeline_color = $plugin_storage->getValue('sytimeline_color') ?: '#3B82F6';
$show_home_btn = $plugin_storage->getValue('show_home_btn') === 'yes';

// 获取事件数据
$events = json_decode($plugin_storage->getValue('events') ?: '[]', true); 

// 按日期排序事件 
usort($events, function($a, $b) {     
    return strtotime($b['date']) - strtotime($a['date']); 
}); 

// 将事件分为左右两栏
$leftEvents = [];
$rightEvents = [];
foreach ($events as $index => $event) {
    if ($index % 2 == 0) {
        $leftEvents[] = $event;
    } else {
        $rightEvents[] = $event;
    }
}

// 格式化日期显示
function formatDate($dateString) {
    $timestamp = strtotime($dateString);
    return date('Y年m月d日', $timestamp);
}

// 获取事件重要性相关样式和图标
function getImportanceStyle($importance) {
    $styles = [
        'normal' => [
            'class' => '',
            'dot_class' => '',
            'icon' => '',
            'color' => 'var(--theme-color)'
        ],
        'important' => [
            'class' => 'sytimeline-important',
            'dot_class' => 'sytimeline-dot-important',
            'icon' => '<i class="fas fa-star sytimeline-importance-icon" title="重要事件"></i>',
            'color' => '#ffc107'
        ],
        'critical' => [
            'class' => 'sytimeline-critical',
            'dot_class' => 'sytimeline-dot-critical',
            'icon' => '<i class="fas fa-exclamation-circle sytimeline-importance-icon" title="关键事件"></i>',
            'color' => '#dc3545'
        ]
    ];
    
    return $styles[$importance] ?? $styles['normal'];
}

// 格式化事件内容，处理链接和重要性
function formatEventContent($event) {
    // 不再使用htmlspecialchars，以支持HTML内容
    $content = $event['content'];
    $importance = isset($event['importance']) ? $event['importance'] : 'normal';
    $importanceStyle = getImportanceStyle($importance);
    
    // 添加重要性图标（如果不是普通事件）
    $importanceIcon = $importance !== 'normal' ? $importanceStyle['icon'] : '';
    
    // 如果有链接，将内容包装为链接
    if (isset($event['link']) && !empty($event['link'])) {
        $content = '<a href="' . htmlspecialchars($event['link']) . '" target="_blank" class="sytimeline-event-link">' . $content . ' <i class="sytimeline-link-icon"></i></a>';
    }
    
    return $importanceIcon . ' ' . $content;
}
?> 
<!DOCTYPE html> 
<html lang="zh-CN"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1">     
    <title><?php echo htmlspecialchars($timeline_title); ?> - <?php echo htmlspecialchars($blogname); ?></title>     
    <?php      
    // 引入站点头部，确保可以使用站点的样式和脚本     
    doAction('index_head');      
    ?>     
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        :root {
            --theme-color: <?php echo $timeline_color; ?>;
            --theme-color-light: <?php echo $timeline_color . '33'; ?>; /* 添加透明度 */
            --theme-color-lighter: <?php echo $timeline_color . '15'; ?>; /* 更淡的透明度 */
            --important-color: #ffc107;
            --important-color-light: #ffc10733;
            --critical-color: #dc3545;
            --critical-color-light: #dc354533;
        }
        
        /* 共享样式 */
        .sytimeline-title {
            color: var(--theme-color);
        }
        
        /* 桌面端样式 */
        .sytimeline-container {
            position: relative;
        }
        
        .sytimeline-center-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--theme-color);
            transform: translateX(-50%);
        }
        
        .sytimeline-item {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s ease;
            position: relative;
            margin-bottom: 2rem;
        }
        
        .sytimeline-item-animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        .sytimeline-dot {
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--theme-color);
            top: 24px;
            z-index: 10;
            box-shadow: 0 0 0 6px var(--theme-color-light);
        }
        
        /* 重要事件和关键事件的圆点样式 */
        .sytimeline-dot-important {
            background-color: var(--important-color);
            box-shadow: 0 0 0 6px var(--important-color-light);
        }
        
        .sytimeline-dot-critical {
            background-color: var(--critical-color);
            box-shadow: 0 0 0 6px var(--critical-color-light);
        }
        
        .sytimeline-left .sytimeline-dot {
            right: -10px;
        }
        
        .sytimeline-right .sytimeline-dot {
            left: -10px;
        }
        
        .sytimeline-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border-left: 4px solid var(--theme-color);
        }
        
        /* 重要事件和关键事件的卡片样式 */
        .sytimeline-important .sytimeline-card {
            border-left: 4px solid var(--important-color);
        }
        
        .sytimeline-critical .sytimeline-card {
            border-left: 4px solid var(--critical-color);
        }
        
        .sytimeline-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-5px);
        }
        
        .sytimeline-date {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background-color: var(--theme-color-light);
            color: var(--theme-color);
            border-radius: 9999px;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        /* 重要事件和关键事件的日期样式 */
        .sytimeline-important .sytimeline-date {
            background-color: var(--important-color-light);
            color: var(--important-color);
        }
        
        .sytimeline-critical .sytimeline-date {
            background-color: var(--critical-color-light);
            color: var(--critical-color);
        }
        
        .sytimeline-content {
            color: #4B5563;
            line-height: 1.6;
        }
        
        /* 重要性图标样式 */
        .sytimeline-importance-icon {
            margin-right: 5px;
        }
        
        /* 支持HTML内容中的图片样式 */
        .sytimeline-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.25rem;
            margin: 0.5rem 0;
        }
        
        .sytimeline-content a:not(.sytimeline-event-link) {
            color: var(--theme-color);
            text-decoration: underline;
            transition: opacity 0.2s ease;
        }
        
        .sytimeline-content a:not(.sytimeline-event-link):hover {
            opacity: 0.8;
        }
        
        .sytimeline-event-link {
            color: inherit;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .sytimeline-event-link:hover {
            text-decoration: underline;
        }
        
        .sytimeline-link-icon::before {
            content: "\f0c1"; /* 链接图标 */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-left: 5px;
            font-size: 0.85em;
        }
        
        .home-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: var(--theme-color);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .home-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* 移动端样式 */
        @media (max-width: 768px) {
            .desktop-sytimeline {
                display: none;
            }
            
            .mobile-sytimeline {
                display: block;
            }
            
            .sytimeline-center-line {
                display: none;
            }
            
            .sytimeline-mobile-container {
                padding-left: 1.5rem;
                position: relative;
            }
            
            .sytimeline-mobile-line {
                position: absolute;
                left: 9px;
                top: 0;
                bottom: 0;
                width: 2px;
                background-color: var(--theme-color-light);
            }
            
            .sytimeline-item-mobile {
                position: relative;
                margin-bottom: 1.5rem;
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInUp 0.5s ease forwards;
                animation-delay: calc(var(--animation-order) * 0.1s);
            }
            
            .sytimeline-dot-mobile {
                position: absolute;
                width: 14px;
                height: 14px;
                border-radius: 50%;
                background-color: var(--theme-color);
                left: -22px;
                top: 8px;
                z-index: 10;
                box-shadow: 0 0 0 4px var(--theme-color-light);
            }
            
            /* 移动端重要事件和关键事件的圆点样式 */
            .sytimeline-item-mobile.sytimeline-important .sytimeline-dot-mobile {
                background-color: var(--important-color);
                box-shadow: 0 0 0 4px var(--important-color-light);
            }
            
            .sytimeline-item-mobile.sytimeline-critical .sytimeline-dot-mobile {
                background-color: var(--critical-color);
                box-shadow: 0 0 0 4px var(--critical-color-light);
            }
            
            .sytimeline-card-mobile {
                background-color: white;
                border-radius: 0.5rem;
                padding: 1rem;
                box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px 0 rgba(0,0,0,0.06);
                border-left: 3px solid var(--theme-color);
            }
            
            /* 移动端重要事件和关键事件的卡片样式 */
            .sytimeline-item-mobile.sytimeline-important .sytimeline-card-mobile {
                border-left: 3px solid var(--important-color);
            }
            
            .sytimeline-item-mobile.sytimeline-critical .sytimeline-card-mobile {
                border-left: 3px solid var(--critical-color);
            }
            
            .sytimeline-date-mobile {
                display: inline-block;
                padding: 0.2rem 0.5rem;
                background-color: var(--theme-color-light);
                color: var(--theme-color);
                border-radius: 9999px;
                font-size: 0.9rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
            }
            
            /* 移动端重要事件和关键事件的日期样式 */
            .sytimeline-item-mobile.sytimeline-important .sytimeline-date-mobile {
                background-color: var(--important-color-light);
                color: var(--important-color);
            }
            
            .sytimeline-item-mobile.sytimeline-critical .sytimeline-date-mobile {
                background-color: var(--critical-color-light);
                color: var(--critical-color);
            }
            
            .sytimeline-content-mobile {
                color: #4B5563;
                font-size: 0.95rem;
                line-height: 1.5;
            }
            
            .home-button {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
            }
        }
        
        @media (min-width: 769px) {
            .desktop-sytimeline {
                display: flex;
            }
            
            .mobile-sytimeline {
                display: none;
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style> 
</head> 
<body class="bg-gray-50">     
    <?php      
    // 引入站点头部     
    if (function_exists('View::getView')) {         
        $header_path = View::getView('header');         
        if (file_exists($header_path)) {             
            include $header_path;         
        }     
    }     
    ?>          
    <div class="container mx-auto px-4 py-12 max-w-5xl">         
        <h1 class="text-4xl font-bold text-center sytimeline-title mb-4"><?php echo htmlspecialchars($timeline_title); ?></h1>  
        <p class="text-center text-gray-500 mb-12"><?php echo htmlspecialchars($timeline_subtitle); ?></p>
                
        <div class="sytimeline-container">
            <!-- 桌面端双栏时间轴 -->
            <div class="sytimeline-center-line"></div>
            
            <?php if (!empty($events)): ?>
                <!-- 桌面端时间轴 -->
                <div class="desktop-sytimeline flex-wrap -mx-4">
                    <!-- 左侧时间轴 -->
                    <div class="w-1/2 px-4">
                        <?php foreach ($leftEvents as $index => $event): 
                            $importance = isset($event['importance']) ? $event['importance'] : 'normal';
                            $importanceStyle = getImportanceStyle($importance);
                        ?>
                        <div class="sytimeline-item sytimeline-left pr-8 <?php echo $importanceStyle['class']; ?>">
                            <div class="sytimeline-dot <?php echo $importanceStyle['dot_class']; ?>"></div>
                            <div class="sytimeline-card">
                                <div class="sytimeline-date">
                                    <?php echo formatDate($event['date']); ?>
                                </div>
                                <div class="sytimeline-content">
                                    <?php echo formatEventContent($event); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 右侧时间轴 -->
                    <div class="w-1/2 px-4">
                        <?php foreach ($rightEvents as $index => $event): 
                            $importance = isset($event['importance']) ? $event['importance'] : 'normal';
                            $importanceStyle = getImportanceStyle($importance);
                        ?>
                        <div class="sytimeline-item sytimeline-right pl-8 <?php echo $importanceStyle['class']; ?>">
                            <div class="sytimeline-dot <?php echo $importanceStyle['dot_class']; ?>"></div>
                            <div class="sytimeline-card">
                                <div class="sytimeline-date">
                                    <?php echo formatDate($event['date']); ?>
                                </div>
                                <div class="sytimeline-content">
                                    <?php echo formatEventContent($event); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 移动端时间轴 -->
                <div class="mobile-sytimeline">
                    <div class="sytimeline-mobile-container">
                        <div class="sytimeline-mobile-line"></div>
                        <?php foreach ($events as $index => $event): 
                            $importance = isset($event['importance']) ? $event['importance'] : 'normal';
                            $importanceStyle = getImportanceStyle($importance);
                        ?>
                        <div class="sytimeline-item-mobile <?php echo $importanceStyle['class']; ?>" style="--animation-order: <?php echo $index; ?>">
                            <div class="sytimeline-dot-mobile <?php echo $importanceStyle['dot_class']; ?>"></div>
                            <div class="sytimeline-card-mobile">
                                <div class="sytimeline-date-mobile">
                                    <?php echo formatDate($event['date']); ?>
                                </div>
                                <div class="sytimeline-content-mobile">
                                    <?php echo formatEventContent($event); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 text-xl">暂无大事记</p>
            <?php endif; ?>
        </div>
    </div>     
    
    <?php if ($show_home_btn): ?>
    <a href="<?php echo BLOG_URL; ?>" class="home-button" title="返回首页">
        <i class="fas fa-home"></i>
    </a>
    <?php endif; ?>
    
    <?php      
    // 引入站点底部     
    if (function_exists('View::getView')) {         
        $footer_path = View::getView('footer');         
        if (file_exists($footer_path)) {             
            include $footer_path;         
        }     
    }     
    ?>     
    
    <script>     
    document.addEventListener('DOMContentLoaded', function() {
        // 桌面端动画
        const timelineItems = document.querySelectorAll('.sytimeline-item');
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry, index) {
                if (entry.isIntersecting) {
                    setTimeout(function() {
                        entry.target.classList.add('sytimeline-item-animate');
                    }, index * 150);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        });
        
        timelineItems.forEach(function(item) {
            observer.observe(item);
        });
    });
    </script> 
</body> 
</html>