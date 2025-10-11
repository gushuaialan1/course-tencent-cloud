-- =====================================================
-- 更新管理员角色权限 - 添加知识图谱和作业系统权限
-- 执行方法：
-- 1. 方法1：在宝塔面板 -> 数据库 -> 管理 -> SQL窗口中执行
-- 2. 方法2：命令行执行: mysql -u用户名 -p 数据库名 < update_admin_permissions.sql
-- =====================================================

-- 查看当前管理员角色的权限（执行前查看）
SELECT id, name, routes FROM kg_role WHERE id = 1;

-- 更新管理员角色权限（添加所有新功能权限）
UPDATE kg_role 
SET 
    routes = JSON_ARRAY(
        -- 原有基础权限（保留所有已有权限）
        'admin.course.list',
        'admin.course.search',
        'admin.course.add',
        'admin.course.edit',
        'admin.course.delete',
        'admin.course.create',
        'admin.course.update',
        'admin.course.restore',
        'admin.course.batch_delete',
        'admin.course.category',
        'admin.course.chapters',
        'admin.course.resources',
        
        'admin.package.list',
        'admin.package.search',
        'admin.package.add',
        'admin.package.edit',
        'admin.package.delete',
        'admin.package.create',
        'admin.package.update',
        'admin.package.restore',
        'admin.package.batch_delete',
        
        'admin.topic.list',
        'admin.topic.search',
        'admin.topic.add',
        'admin.topic.edit',
        'admin.topic.delete',
        'admin.topic.create',
        'admin.topic.update',
        'admin.topic.restore',
        'admin.topic.batch_delete',
        
        'admin.article.list',
        'admin.article.search',
        'admin.article.add',
        'admin.article.edit',
        'admin.article.delete',
        'admin.article.show',
        'admin.article.create',
        'admin.article.update',
        'admin.article.restore',
        'admin.article.moderate',
        'admin.article.report',
        'admin.article.batch_delete',
        'admin.article.batch_moderate',
        'admin.article.category',
        
        'admin.question.list',
        'admin.question.search',
        'admin.question.add',
        'admin.question.edit',
        'admin.question.delete',
        'admin.question.show',
        'admin.question.create',
        'admin.question.update',
        'admin.question.restore',
        'admin.question.moderate',
        'admin.question.report',
        'admin.question.batch_delete',
        'admin.question.batch_moderate',
        'admin.question.category',
        
        'admin.answer.list',
        'admin.answer.search',
        'admin.answer.add',
        'admin.answer.edit',
        'admin.answer.delete',
        'admin.answer.show',
        'admin.answer.create',
        'admin.answer.update',
        'admin.answer.restore',
        'admin.answer.moderate',
        'admin.answer.report',
        'admin.answer.batch_delete',
        'admin.answer.batch_moderate',
        
        'admin.comment.list',
        'admin.comment.search',
        'admin.comment.edit',
        'admin.comment.delete',
        'admin.comment.update',
        'admin.comment.restore',
        'admin.comment.moderate',
        'admin.comment.report',
        'admin.comment.batch_delete',
        'admin.comment.batch_moderate',
        
        'admin.nav.list',
        'admin.nav.add',
        'admin.nav.edit',
        'admin.nav.delete',
        'admin.nav.create',
        'admin.nav.update',
        'admin.nav.restore',
        
        'admin.page.list',
        'admin.page.add',
        'admin.page.edit',
        'admin.page.delete',
        'admin.page.create',
        'admin.page.update',
        'admin.page.restore',
        
        'admin.help.list',
        'admin.help.add',
        'admin.help.edit',
        'admin.help.delete',
        'admin.help.create',
        'admin.help.update',
        'admin.help.restore',
        'admin.help.category',
        
        'admin.tag.list',
        'admin.tag.search',
        'admin.tag.add',
        'admin.tag.edit',
        'admin.tag.delete',
        'admin.tag.create',
        'admin.tag.update',
        'admin.tag.restore',
        
        'admin.category.list',
        'admin.category.add',
        'admin.category.edit',
        'admin.category.delete',
        'admin.category.create',
        'admin.category.update',
        'admin.category.restore',
        
        'admin.chapter.add',
        'admin.chapter.edit',
        'admin.chapter.delete',
        'admin.chapter.create',
        'admin.chapter.update',
        'admin.chapter.content',
        'admin.chapter.restore',
        'admin.chapter.lessons',
        
        -- 【新增】知识图谱权限
        'admin.knowledge_graph.list',
        'admin.knowledge_graph.editor',
        'admin.knowledge_graph.nodes',
        'admin.knowledge_graph.node_create',
        'admin.knowledge_graph.node_edit',
        'admin.knowledge_graph.create_node',
        'admin.knowledge_graph.update_node',
        'admin.knowledge_graph.delete_node',
        'admin.knowledge_graph.create_relation',
        'admin.knowledge_graph.update_relation',
        'admin.knowledge_graph.delete_relation',
        'admin.knowledge_graph.data',
        'admin.knowledge_graph.save',
        'admin.knowledge_graph.analysis',
        'admin.knowledge_graph.export',
        'admin.knowledge_graph.templates',
        'admin.knowledge_graph.template_detail',
        'admin.knowledge_graph.apply_template',
        'admin.knowledge_graph.template_create',
        'admin.knowledge_graph.template_update',
        'admin.knowledge_graph.template_delete',
        
        -- 【新增】作业系统权限
        'admin.assignment.list',
        'admin.assignment.create',
        'admin.assignment.search',
        'admin.assignment.stats',
        'admin.assignment.edit',
        'admin.assignment.show',
        'admin.assignment.store',
        'admin.assignment.update',
        'admin.assignment.delete',
        'admin.assignment.restore',
        'admin.assignment.publish',
        'admin.assignment.close',
        'admin.assignment_submission.list',
        'admin.assignment_submission.detail',
        'admin.assignment_submission.review',
        'admin.assignment_submission.update_score',
        'admin.assignment_submission.batch_review',
        
        -- 运营管理权限
        'admin.stat.hot_sales',
        'admin.stat.sales',
        'admin.stat.refunds',
        'admin.stat.reg_users',
        'admin.stat.online_users',
        
        'admin.vip.list',
        'admin.vip.add',
        'admin.vip.edit',
        'admin.vip.delete',
        'admin.vip.create',
        'admin.vip.update',
        'admin.vip.restore',
        
        'admin.point_gift.list',
        'admin.point_gift.add',
        'admin.point_gift.edit',
        'admin.point_gift.delete',
        'admin.point_gift.create',
        'admin.point_gift.update',
        'admin.point_gift.restore',
        
        'admin.point_gift_redeem.list',
        'admin.point_history.list',
        
        'admin.consult.list',
        'admin.consult.search',
        'admin.consult.edit',
        'admin.consult.delete',
        'admin.consult.update',
        'admin.consult.restore',
        'admin.consult.moderate',
        'admin.consult.batch_moderate',
        
        'admin.review.list',
        'admin.review.search',
        'admin.review.edit',
        'admin.review.delete',
        'admin.review.update',
        'admin.review.restore',
        'admin.review.moderate',
        'admin.review.batch_moderate',
        
        'admin.slide.list',
        'admin.slide.search',
        'admin.slide.add',
        'admin.slide.edit',
        'admin.slide.delete',
        'admin.slide.create',
        'admin.slide.update',
        'admin.slide.restore',
        
        'admin.mod.reviews',
        'admin.mod.consults',
        'admin.mod.articles',
        'admin.mod.questions',
        'admin.mod.answers',
        'admin.mod.comments',
        
        'admin.report.articles',
        'admin.report.questions',
        'admin.report.answers',
        'admin.report.comments',
        
        -- 财务管理权限
        'admin.order.list',
        'admin.order.search',
        'admin.order.show',
        'admin.order.status_history',
        
        'admin.trade.list',
        'admin.trade.search',
        'admin.trade.show',
        'admin.trade.refund',
        'admin.trade.status_history',
        
        'admin.refund.list',
        'admin.refund.search',
        'admin.refund.show',
        'admin.refund.review',
        'admin.refund.status_history',
        
        -- 用户管理权限
        'admin.user.list',
        'admin.user.search',
        'admin.user.add',
        'admin.user.edit',
        'admin.user.create',
        'admin.user.update',
        'admin.user.online',
        
        'admin.role.list',
        'admin.role.add',
        'admin.role.edit',
        'admin.role.delete',
        'admin.role.create',
        'admin.role.update',
        'admin.role.restore',
        
        'admin.audit.list',
        'admin.audit.search',
        'admin.audit.show',
        
        -- 系统设置权限
        'admin.setting.site',
        'admin.setting.contact',
        'admin.setting.oauth',
        'admin.setting.mail',
        'admin.setting.pay',
        'admin.setting.secret',
        'admin.setting.sms',
        'admin.setting.storage',
        'admin.setting.vod',
        'admin.setting.live',
        'admin.setting.point',
        'admin.setting.wechat_oa',
        'admin.setting.dingtalk_robot',
        
        -- 实用工具权限
        'admin.util.index_cache',
        
        -- 【新增】资源管理增强权限
        'admin.resource.upload_enhanced',
        'admin.resource.recent',
        'admin.resource.batch_upload',
        'admin.resource.preview',
        'admin.resource.create',
        'admin.resource.update',
        'admin.resource.delete',
        'admin.resource.restore'
    ),
    update_time = UNIX_TIMESTAMP()
WHERE id = 1;

-- 查看更新后的权限（执行后确认）
SELECT id, name, JSON_LENGTH(routes) as permission_count FROM kg_role WHERE id = 1;

-- 显示详细信息
SELECT 
    id,
    name,
    summary,
    JSON_LENGTH(routes) as '权限数量',
    FROM_UNIXTIME(update_time) as '更新时间'
FROM kg_role 
WHERE id = 1;

