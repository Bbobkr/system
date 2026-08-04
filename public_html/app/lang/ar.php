<?php
return [
    // عام
    'app_name' => 'نظام إدارة المخزون والمبيعات',
    'save' => 'حفظ', 'cancel' => 'إلغاء', 'edit' => 'تعديل', 'delete' => 'حذف',
    'add_new' => 'إضافة جديد', 'search' => 'بحث', 'actions' => 'إجراءات', 'status' => 'الحالة',
    'active' => 'مفعل', 'inactive' => 'غير مفعل', 'yes' => 'نعم', 'no' => 'لا',
    'created_at' => 'تاريخ الإنشاء', 'confirm_delete' => 'هل أنت متأكد من الحذف؟',
    'no_records' => 'لا توجد سجلات', 'back' => 'رجوع', 'print' => 'طباعة', 'view' => 'عرض',
    'no_permission' => 'لا تملك صلاحية الوصول لهذه الصفحة.', 'csrf_error' => 'انتهت صلاحية الجلسة، أعد تحميل الصفحة وحاول مجددًا.',
    'total' => 'الإجمالي', 'subtotal' => 'المجموع الفرعي', 'discount' => 'الخصم', 'tax' => 'الضريبة',
    'note' => 'ملاحظة', 'date' => 'التاريخ', 'all_branches' => 'كل الفروع',

    // القائمة الجانبية
    'nav_dashboard' => 'لوحة التحكم', 'nav_products' => 'المنتجات', 'nav_categories' => 'التصنيفات',
    'nav_branches' => 'الفروع', 'nav_stock' => 'المخزون', 'nav_customers' => 'العملاء',
    'nav_suppliers' => 'الموردون', 'nav_sales' => 'المبيعات', 'nav_purchases' => 'المشتريات',
    'nav_users' => 'المستخدمون', 'nav_reports' => 'التقارير', 'nav_settings' => 'الإعدادات',
    'nav_barcode' => 'الباركود', 'nav_logout' => 'تسجيل الخروج',

    // الدخول
    'login' => 'تسجيل الدخول', 'username' => 'اسم المستخدم', 'password' => 'كلمة المرور',
    'login_failed' => 'اسم المستخدم أو كلمة المرور غير صحيحة، أو الحساب غير مفعل.',
    'welcome' => 'مرحبًا',

    // لوحة التحكم
    'today_sales' => 'مبيعات اليوم', 'low_stock_items' => 'أصناف تحت الحد الأدنى',
    'stock_value' => 'قيمة المخزون', 'total_products' => 'عدد المنتجات',
    'sales_trend' => 'اتجاه المبيعات (آخر 30 يوم)', 'top_products' => 'الأكثر مبيعًا',
    'stock_by_branch' => 'قيمة المخزون حسب الفرع',

    // منتجات
    'product_name' => 'اسم المنتج', 'sku' => 'رمز الصنف', 'barcode' => 'الباركود',
    'category' => 'التصنيف', 'unit' => 'الوحدة', 'cost_price' => 'سعر التكلفة', 'sale_price' => 'سعر البيع',
    'low_stock_threshold' => 'حد التنبيه الأدنى', 'image' => 'الصورة', 'quantity' => 'الكمية',
    'products_list' => 'قائمة المنتجات', 'add_product' => 'إضافة منتج', 'edit_product' => 'تعديل منتج',
    'category_name' => 'اسم التصنيف', 'parent_category' => 'التصنيف الأب',

    // الفروع
    'branch_name' => 'اسم الفرع', 'address' => 'العنوان', 'phone' => 'الهاتف',
    'add_branch' => 'إضافة فرع', 'edit_branch' => 'تعديل فرع',

    // المخزون
    'stock_movements' => 'حركة المخزون', 'stock_in' => 'إدخال مخزون', 'stock_out' => 'إخراج مخزون',
    'stock_transfer' => 'تحويل بين الفروع', 'from_branch' => 'من فرع', 'to_branch' => 'إلى فرع',
    'movement_type' => 'نوع الحركة', 'low_stock_alerts' => 'تنبيهات نقص المخزون', 'current_stock' => 'الكمية الحالية',

    // عملاء وموردون
    'customer_name' => 'اسم العميل', 'supplier_name' => 'اسم المورد', 'email' => 'البريد الإلكتروني',
    'add_customer' => 'إضافة عميل', 'edit_customer' => 'تعديل عميل',
    'add_supplier' => 'إضافة مورد', 'edit_supplier' => 'تعديل مورد', 'walk_in_customer' => 'عميل نقدي',

    // المبيعات والمشتريات
    'invoice_no' => 'رقم الفاتورة', 'customer' => 'العميل', 'supplier' => 'المورد', 'branch' => 'الفرع',
    'new_sale' => 'فاتورة بيع جديدة', 'new_purchase' => 'فاتورة شراء جديدة',
    'add_item' => 'إضافة صنف', 'unit_price' => 'سعر الوحدة', 'unit_cost' => 'سعر التكلفة',
    'payment_status' => 'حالة الدفع', 'paid' => 'مدفوعة', 'partial' => 'مدفوعة جزئيًا', 'unpaid' => 'غير مدفوعة',
    'paid_amount' => 'المبلغ المدفوع', 'save_invoice' => 'حفظ الفاتورة', 'invoice' => 'فاتورة',
    'return' => 'مرتجع', 'sales_returns' => 'مرتجعات المبيعات', 'purchase_returns' => 'مرتجعات المشتريات',
    'new_return' => 'مرتجع جديد', 'original_invoice' => 'الفاتورة الأصلية', 'insufficient_stock' => 'الكمية المتوفرة غير كافية',
    'scan_or_search' => 'امسح الباركود أو ابحث عن منتج...', 'remove' => 'حذف',

    // المستخدمون
    'full_name' => 'الاسم الكامل', 'role' => 'الدور', 'assigned_branch' => 'الفرع المخصص',
    'add_user' => 'إضافة مستخدم', 'edit_user' => 'تعديل مستخدم', 'permissions' => 'الصلاحيات',
    'change_password' => 'تغيير كلمة المرور', 'leave_blank_keep' => 'اتركه فارغًا للإبقاء على كلمة المرور الحالية',

    // التقارير
    'report_sales' => 'تقرير المبيعات', 'report_inventory' => 'تقرير المخزون', 'report_profit' => 'تقرير الأرباح',
    'from_date' => 'من تاريخ', 'to_date' => 'إلى تاريخ', 'profit' => 'الربح', 'revenue' => 'الإيرادات', 'cost' => 'التكلفة',

    // الإعدادات
    'company_name' => 'اسم الشركة', 'currency' => 'العملة', 'language' => 'اللغة', 'arabic' => 'العربية', 'english' => 'English',
    'invoice_settings' => 'إعدادات الفواتير',
];
