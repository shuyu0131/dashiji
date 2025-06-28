<?php
!defined('EMLOG_ROOT') && exit('access denied!');

function plugin_setting_view() {
    $plugin_storage = Storage::getInstance('sytimeline');
    $events = json_decode($plugin_storage->getValue('events') ?: '[]', true);
    ?>
    <div class="d-flex mb-3">
        <h4 class="mb-0">大事记时间轴管理</h4>
        <a href="<?php echo BLOG_URL; ?>?plugin=sytimeline" target="_blank" class="btn btn-sm btn-outline-info ml-auto">预览效果</a>
    </div>
    
    <form action="plugin.php?plugin=sytimeline" method="post">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>事件管理</span>
                <button type="button" id="add-event" class="btn btn-sm btn-primary">
                    <i class="icofont-plus"></i> 添加事件
                </button>
            </div>
            <div class="card-body" id="sytimeline-events-container">
                <?php if (empty($events)): ?>
                <div class="sytimeline-event-row mb-3">
                    <div class="card border-left-primary">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group mb-md-0">
                                        <label>日期：</label>
                                        <input type="date" name="event_date[]" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group mb-md-0">
                                        <label>事件内容：</label>
                                        <textarea name="event_content[]" class="form-control" rows="3" required placeholder="请输入事件描述，支持HTML代码"></textarea>
                                        <small class="form-text text-muted">支持HTML代码，可以添加链接、图片等富文本内容</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-md-0">
                                        <label>链接地址：<small class="text-muted">(可选)</small></label>
                                        <input type="url" name="event_link[]" class="form-control" placeholder="例如: https://www.example.com">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-md-0">
                                        <label>重要程度：</label>
                                        <select name="event_importance[]" class="form-control importance-select">
                                            <option value="normal">普通事件</option>
                                            <option value="important">重要事件</option>
                                            <option value="critical">关键事件</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end justify-content-end">
                                    <button type="button" class="btn btn-danger remove-event mt-md-0 mt-2">
                                        <i class="icofont-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($events as $index => $event): ?>
                    <div class="sytimeline-event-row mb-3">
                        <div class="card border-left-primary">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group mb-md-0">
                                            <label>日期：</label>
                                            <input type="date" name="event_date[]" class="form-control" value="<?php echo $event['date']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group mb-md-0">
                                            <label>事件内容：</label>
                                            <textarea name="event_content[]" class="form-control" rows="3" required placeholder="请输入事件描述，支持HTML代码"><?php echo htmlspecialchars($event['content']); ?></textarea>
                                            <small class="form-text text-muted">支持HTML代码，可以添加链接、图片等富文本内容</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-md-0">
                                            <label>链接地址：<small class="text-muted">(可选)</small></label>
                                            <input type="url" name="event_link[]" class="form-control" value="<?php echo isset($event['link']) ? htmlspecialchars($event['link']) : ''; ?>" placeholder="例如: https://www.example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-md-0">
                                            <label>重要程度：</label>
                                            <select name="event_importance[]" class="form-control importance-select">
                                                <option value="normal" <?php echo (!isset($event['importance']) || $event['importance'] == 'normal') ? 'selected' : ''; ?>>普通事件</option>
                                                <option value="important" <?php echo (isset($event['importance']) && $event['importance'] == 'important') ? 'selected' : ''; ?>>重要事件</option>
                                                <option value="critical" <?php echo (isset($event['importance']) && $event['importance'] == 'critical') ? 'selected' : ''; ?>>关键事件</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-danger remove-event mt-md-0 mt-2">
                                            <i class="icofont-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer text-right">
                <button type="submit" name="timeline_submit" class="btn btn-success">保存设置</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">显示设置</div>
            <div class="card-body">
                <div class="form-group">
                    <label>时间轴标题：</label>
                    <input type="text" name="sytimeline_title" class="form-control" value="<?php echo htmlspecialchars($plugin_storage->getValue('sytimeline_title') ?: '大事记'); ?>" placeholder="时间轴页面标题">
                </div>
                <div class="form-group">
                    <label>副标题：</label>
                    <input type="text" name="sytimeline_subtitle" class="form-control" value="<?php echo htmlspecialchars($plugin_storage->getValue('sytimeline_subtitle') ?: '记录我们走过的每一步重要历程'); ?>" placeholder="时间轴页面副标题">
                </div>
                <div class="form-group">
                    <label>主题颜色：</label>
                    <input type="color" name="sytimeline_color" class="form-control form-control-color" value="<?php echo $plugin_storage->getValue('sytimeline_color') ?: '#3B82F6'; ?>" title="选择主题颜色">
                    <small class="form-text text-muted">时间轴主题颜色，默认为蓝色</small>
                </div>
                <div class="form-group mb-0">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="show_home_btn" name="show_home_btn" value="yes" <?php echo $plugin_storage->getValue('show_home_btn') === 'yes' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="show_home_btn">显示返回首页按钮</label>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" name="timeline_submit" class="btn btn-success">保存设置</button>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('sytimeline-events-container');
        const addButton = document.getElementById('add-event');
        const colorPicker = document.querySelector('input[name="sytimeline_color"]');

        // 添加事件
        addButton.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.className = 'sytimeline-event-row mb-3';
            newRow.innerHTML = `
                <div class="card border-left-primary">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label>日期：</label>
                                    <input type="date" name="event_date[]" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-md-0">
                                    <label>事件内容：</label>
                                    <textarea name="event_content[]" class="form-control" rows="3" required placeholder="请输入事件描述，支持HTML代码"></textarea>
                                    <small class="form-text text-muted">支持HTML代码，可以添加链接、图片等富文本内容</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label>链接地址：<small class="text-muted">(可选)</small></label>
                                    <input type="url" name="event_link[]" class="form-control" placeholder="例如: https://www.example.com">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label>重要程度：</label>
                                    <select name="event_importance[]" class="form-control importance-select">
                                        <option value="normal">普通事件</option>
                                        <option value="important">重要事件</option>
                                        <option value="critical">关键事件</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-danger remove-event mt-md-0 mt-2">
                                    <i class="icofont-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            
            // 为新添加的下拉菜单添加样式变化事件
            updateImportanceStyles(newRow.querySelector('.importance-select'));
        });

        // 删除事件
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-event') || e.target.closest('.remove-event')) {
                // 确保始终保留至少一个输入行
                if (container.querySelectorAll('.sytimeline-event-row').length > 1) {
                    const button = e.target.classList.contains('remove-event') ? 
                                  e.target : e.target.closest('.remove-event');
                    button.closest('.sytimeline-event-row').remove();
                } else {
                    alert('至少需要保留一个事件');
                }
            }
        });
        
        // 为颜色选择器添加实时预览效果
        if (colorPicker) {
            colorPicker.addEventListener('input', function() {
                document.querySelectorAll('.border-left-primary').forEach(card => {
                    card.style.borderLeftColor = this.value;
                    card.style.borderLeftWidth = '4px';
                });
            });
            
            // 初始化时设置颜色
            document.querySelectorAll('.border-left-primary').forEach(card => {
                card.style.borderLeftColor = colorPicker.value;
                card.style.borderLeftWidth = '4px';
            });
        }
        
        // 为重要程度下拉菜单添加样式变化
        function updateImportanceStyles(selectElement) {
            function updateStyle() {
                const card = selectElement.closest('.card');
                // 移除所有重要程度相关的类
                card.classList.remove('border-left-primary', 'border-left-warning', 'border-left-danger');
                
                // 根据选中的值添加相应的类
                switch(selectElement.value) {
                    case 'normal':
                        card.classList.add('border-left-primary');
                        card.style.borderLeftColor = colorPicker.value;
                        break;
                    case 'important':
                        card.classList.add('border-left-warning');
                        card.style.borderLeftColor = '#ffc107';
                        break;
                    case 'critical':
                        card.classList.add('border-left-danger');
                        card.style.borderLeftColor = '#dc3545';
                        break;
                }
            }
            
            selectElement.addEventListener('change', updateStyle);
            // 初始化样式
            updateStyle();
        }
        
        // 初始化所有已有的重要程度下拉菜单样式
        document.querySelectorAll('.importance-select').forEach(select => {
            updateImportanceStyles(select);
        });
    });
    </script>
    <?php
}

// 处理表单提交
if (isset($_POST['timeline_submit'])) {
    // 安全过滤函数
    function safe_value($val) {
        if (is_array($val)) {
            return array_map('safe_value', $val);
        }
        return trim(addslashes($val));
    }
    
    // 获取并安全过滤POST数据
    $dates = isset($_POST['event_date']) ? safe_value($_POST['event_date']) : [];
    $contents = isset($_POST['event_content']) ? $_POST['event_content'] : []; // 内容允许HTML，不过滤
    $links = isset($_POST['event_link']) ? safe_value($_POST['event_link']) : [];
    $importances = isset($_POST['event_importance']) ? safe_value($_POST['event_importance']) : [];
    
    $events = [];
    for ($i = 0; $i < count($dates); $i++) {
        // 确保日期和内容都不为空
        if (!empty($dates[$i]) && !empty($contents[$i])) {
            $event = [
                'date' => $dates[$i],
                'content' => trim($contents[$i]), // 内容允许HTML，不转义
                'importance' => isset($importances[$i]) ? $importances[$i] : 'normal'
            ];
            
            // 如果有链接，添加到事件中
            if (!empty($links[$i])) {
                $event['link'] = trim($links[$i]);
            }
            
            $events[] = $event;
        }
    }
    
    // 如果没有事件，给出提示
    if (empty($events)) {
        emMsg('请至少添加一个事件', 'plugin.php?plugin=sytimeline');
        exit;
    }
    
    // 按日期倒序排序
    usort($events, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    $plugin_storage = Storage::getInstance('sytimeline');
    $plugin_storage->setValue('events', json_encode($events), 'array');
    
    // 保存显示设置 - 安全过滤输入
    $sytimeline_title = isset($_POST['sytimeline_title']) ? safe_value($_POST['sytimeline_title']) : '大事记';
    $sytimeline_subtitle = isset($_POST['sytimeline_subtitle']) ? safe_value($_POST['sytimeline_subtitle']) : '记录我们走过的每一步重要历程';
    $sytimeline_color = isset($_POST['sytimeline_color']) ? safe_value($_POST['sytimeline_color']) : '#3B82F6';
    $show_home_btn = isset($_POST['show_home_btn']) ? 'yes' : 'no';
    
    $plugin_storage->setValue('sytimeline_title', $sytimeline_title);
    $plugin_storage->setValue('sytimeline_subtitle', $sytimeline_subtitle);
    $plugin_storage->setValue('sytimeline_color', $sytimeline_color);
    $plugin_storage->setValue('show_home_btn', $show_home_btn);
    
    emMsg('大事记设置已保存', 'plugin.php?plugin=sytimeline');
}