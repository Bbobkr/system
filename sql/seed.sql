-- =====================================================================
-- بيانات أولية: الأدوار، الصلاحيات، فرع افتراضي، إعدادات عامة
-- استورده بعد schema.sql
-- ملاحظة: لا يُنشئ هذا الملف حساب المدير (Admin) — لأسباب أمنية يتم إنشاؤه
-- عبر صفحة public_html/setup.php في أول مرة يعمل فيها الموقع (راجع README).
-- =====================================================================

INSERT INTO roles (id, slug, name, name_ar) VALUES
    (1, 'admin', 'Administrator', 'مدير النظام'),
    (2, 'manager', 'Manager', 'مدير فرع'),
    (3, 'employee', 'Employee', 'موظف');

-- صلاحيات كاملة للأدمن (is_admin() يتجاوز الفحص فعليًا، لكن نسجلها للوضوح في واجهة الصلاحيات)
INSERT INTO permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES
(1,'products',1,1,1,1),(1,'categories',1,1,1,1),(1,'branches',1,1,1,1),(1,'stock',1,1,1,1),
(1,'customers',1,1,1,1),(1,'suppliers',1,1,1,1),(1,'sales',1,1,1,1),(1,'purchases',1,1,1,1),
(1,'users',1,1,1,1),(1,'reports',1,1,1,1),(1,'settings',1,1,1,1);

-- مدير فرع: كل شيء عدا إدارة المستخدمين والإعدادات (يستطيع فقط عرضها)
INSERT INTO permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES
(2,'products',1,1,1,1),(2,'categories',1,1,1,1),(2,'branches',1,1,1,0),(2,'stock',1,1,1,1),
(2,'customers',1,1,1,1),(2,'suppliers',1,1,1,1),(2,'sales',1,1,1,1),(2,'purchases',1,1,1,1),
(2,'users',1,0,0,0),(2,'reports',1,0,0,0),(2,'settings',1,0,0,0);

-- موظف: تشغيل يومي فقط (بيع، عملاء) وعرض بسيط لما دون ذلك
INSERT INTO permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES
(3,'products',1,0,0,0),(3,'categories',1,0,0,0),(3,'branches',1,0,0,0),(3,'stock',1,0,0,0),
(3,'customers',1,1,1,0),(3,'suppliers',1,0,0,0),(3,'sales',1,1,0,0),(3,'purchases',1,0,0,0),
(3,'users',0,0,0,0),(3,'reports',0,0,0,0),(3,'settings',0,0,0,0);

INSERT INTO branches (id, name, address, phone, is_active) VALUES
    (1, 'الفرع الرئيسي', NULL, NULL, 1);

INSERT INTO settings (setting_key, setting_value) VALUES
    ('company_name', 'شركتي'),
    ('currency', 'ر.س'),
    ('default_language', 'ar'),
    ('invoice_prefix_sale', 'S-'),
    ('invoice_prefix_purchase', 'P-'),
    ('default_low_stock_threshold', '5');
