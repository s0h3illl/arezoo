<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'پذیرفتن :attribute الزامی است.',
    'accepted_if' => 'وقتی :other برابر :value است، پذیرفتن :attribute الزامی است.',
    'active_url' => ':attribute باید یک نشانی اینترنتی معتبر باشد.',
    'after' => ':attribute باید تاریخی بعد از :date باشد.',
    'after_or_equal' => ':attribute باید تاریخی برابر یا بعد از :date باشد.',
    'alpha' => ':attribute باید فقط شامل حروف باشد.',
    'alpha_dash' => ':attribute باید فقط شامل حروف، اعداد، خط تیره و زیرخط باشد.',
    'alpha_num' => ':attribute باید فقط شامل حروف و اعداد باشد.',
    'any_of' => ':attribute معتبر نیست.',
    'array' => ':attribute باید یک آرایه باشد.',
    'ascii' => ':attribute باید فقط شامل حروف، اعداد و نمادهای تک‌بایتی باشد.',
    'base64' => ':attribute باید یک رشته‌ی Base64 معتبر باشد.',
    'before' => ':attribute باید تاریخی قبل از :date باشد.',
    'before_or_equal' => ':attribute باید تاریخی برابر یا قبل از :date باشد.',
    'between' => [
        'array' => ':attribute باید بین :min و :max مورد داشته باشد.',
        'file' => 'حجم :attribute باید بین :min و :max کیلوبایت باشد.',
        'numeric' => ':attribute باید بین :min و :max باشد.',
        'string' => ':attribute باید بین :min و :max کاراکتر باشد.',
    ],
    'boolean' => ':attribute باید درست یا نادرست باشد.',
    'can' => ':attribute شامل مقداری غیرمجاز است.',
    'confirmed' => ':attribute با تکرارش یکی نیست.',
    'contains' => ':attribute یکی از مقادیر لازم را ندارد.',
    'current_password' => 'رمز عبور وارد شده درست نیست.',
    'date' => ':attribute باید یک تاریخ معتبر باشد.',
    'date_equals' => ':attribute باید تاریخی برابر با :date باشد.',
    'date_format' => ':attribute باید با قالب :format بخواند.',
    'decimal' => ':attribute باید :decimal رقم اعشار داشته باشد.',
    'declined' => 'رد کردن :attribute الزامی است.',
    'declined_if' => 'وقتی :other برابر :value است، رد کردن :attribute الزامی است.',
    'different' => ':attribute و :other باید با هم فرق داشته باشند.',
    'digits' => ':attribute باید :digits رقم باشد.',
    'digits_between' => ':attribute باید بین :min و :max رقم باشد.',
    'dimensions' => 'ابعاد تصویر :attribute معتبر نیست.',
    'distinct' => ':attribute مقداری تکراری دارد.',
    'doesnt_contain' => ':attribute نباید شامل هیچ‌کدام از این موارد باشد: :values.',
    'doesnt_end_with' => ':attribute نباید به هیچ‌کدام از این موارد ختم شود: :values.',
    'doesnt_start_with' => ':attribute نباید با هیچ‌کدام از این موارد شروع شود: :values.',
    'email' => ':attribute باید یک نشانی ایمیل معتبر باشد.',
    'encoding' => ':attribute باید با :encoding کدگذاری شده باشد.',
    'ends_with' => ':attribute باید به یکی از این موارد ختم شود: :values.',
    'enum' => ':attribute انتخاب‌شده معتبر نیست.',
    'exists' => ':attribute انتخاب‌شده معتبر نیست.',
    'extensions' => ':attribute باید یکی از این پسوندها را داشته باشد: :values.',
    'file' => ':attribute باید یک فایل باشد.',
    'filled' => ':attribute باید مقدار داشته باشد.',
    'gt' => [
        'array' => ':attribute باید بیشتر از :value مورد داشته باشد.',
        'file' => 'حجم :attribute باید بیشتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید بزرگ‌تر از :value باشد.',
        'string' => ':attribute باید بیشتر از :value کاراکتر باشد.',
    ],
    'gte' => [
        'array' => ':attribute باید :value مورد یا بیشتر داشته باشد.',
        'file' => 'حجم :attribute باید برابر یا بیشتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر یا بزرگ‌تر از :value باشد.',
        'string' => ':attribute باید برابر یا بیشتر از :value کاراکتر باشد.',
    ],
    'hex_color' => ':attribute باید یک رنگ هگزادسیمال معتبر باشد.',
    'image' => ':attribute باید یک تصویر باشد.',
    'in' => ':attribute انتخاب‌شده معتبر نیست.',
    'in_array' => ':attribute باید در :other موجود باشد.',
    'in_array_keys' => ':attribute باید دست‌کم یکی از این کلیدها را داشته باشد: :values.',
    'integer' => ':attribute باید یک عدد صحیح باشد.',
    'ip' => ':attribute باید یک نشانی IP معتبر باشد.',
    'ipv4' => ':attribute باید یک نشانی IPv4 معتبر باشد.',
    'ipv6' => ':attribute باید یک نشانی IPv6 معتبر باشد.',
    'json' => ':attribute باید یک رشته‌ی JSON معتبر باشد.',
    'list' => ':attribute باید یک فهرست باشد.',
    'lowercase' => ':attribute باید با حروف کوچک باشد.',
    'lt' => [
        'array' => ':attribute باید کمتر از :value مورد داشته باشد.',
        'file' => 'حجم :attribute باید کمتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر از :value باشد.',
        'string' => ':attribute باید کمتر از :value کاراکتر باشد.',
    ],
    'lte' => [
        'array' => ':attribute نباید بیشتر از :value مورد داشته باشد.',
        'file' => 'حجم :attribute باید برابر یا کمتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر یا کوچک‌تر از :value باشد.',
        'string' => ':attribute باید برابر یا کمتر از :value کاراکتر باشد.',
    ],
    'mac_address' => ':attribute باید یک نشانی MAC معتبر باشد.',
    'max' => [
        'array' => ':attribute نباید بیشتر از :max مورد داشته باشد.',
        'file' => 'حجم :attribute نباید بیشتر از :max کیلوبایت باشد.',
        'numeric' => ':attribute نباید بزرگ‌تر از :max باشد.',
        'string' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
    ],
    'max_digits' => ':attribute نباید بیشتر از :max رقم داشته باشد.',
    'mimes' => ':attribute باید فایلی از نوع :values باشد.',
    'mimetypes' => ':attribute باید فایلی از نوع :values باشد.',
    'min' => [
        'array' => ':attribute باید دست‌کم :min مورد داشته باشد.',
        'file' => 'حجم :attribute باید دست‌کم :min کیلوبایت باشد.',
        'numeric' => ':attribute باید دست‌کم :min باشد.',
        'string' => ':attribute باید دست‌کم :min کاراکتر باشد.',
    ],
    'min_digits' => ':attribute باید دست‌کم :min رقم داشته باشد.',
    'missing' => ':attribute نباید موجود باشد.',
    'missing_if' => 'وقتی :other برابر :value است، :attribute نباید موجود باشد.',
    'missing_unless' => 'مگر آنکه :other برابر :value باشد، :attribute نباید موجود باشد.',
    'missing_with' => 'وقتی :values موجود است، :attribute نباید موجود باشد.',
    'missing_with_all' => 'وقتی :values موجود هستند، :attribute نباید موجود باشد.',
    'multiple_of' => ':attribute باید مضربی از :value باشد.',
    'not_in' => ':attribute انتخاب‌شده معتبر نیست.',
    'not_regex' => 'قالب :attribute معتبر نیست.',
    'numeric' => ':attribute باید یک عدد باشد.',
    'password' => [
        'letters' => ':attribute باید دست‌کم یک حرف داشته باشد.',
        'mixed' => ':attribute باید دست‌کم یک حرف بزرگ و یک حرف کوچک داشته باشد.',
        'numbers' => ':attribute باید دست‌کم یک عدد داشته باشد.',
        'symbols' => ':attribute باید دست‌کم یک نماد داشته باشد.',
        'uncompromised' => 'این :attribute در یک نشت اطلاعات دیده شده است. لطفاً :attribute دیگری انتخاب کنید.',
    ],
    'present' => ':attribute باید موجود باشد.',
    'present_if' => 'وقتی :other برابر :value است، :attribute باید موجود باشد.',
    'present_unless' => 'مگر آنکه :other برابر :value باشد، :attribute باید موجود باشد.',
    'present_with' => 'وقتی :values موجود است، :attribute باید موجود باشد.',
    'present_with_all' => 'وقتی :values موجود هستند، :attribute باید موجود باشد.',
    'prohibited' => ':attribute مجاز نیست.',
    'prohibited_if' => 'وقتی :other برابر :value است، :attribute مجاز نیست.',
    'prohibited_if_accepted' => 'وقتی :other پذیرفته شده باشد، :attribute مجاز نیست.',
    'prohibited_if_declined' => 'وقتی :other رد شده باشد، :attribute مجاز نیست.',
    'prohibited_unless' => 'مگر آنکه :other در :values باشد، :attribute مجاز نیست.',
    'prohibits' => ':attribute باعث می‌شود :other مجاز نباشد.',
    'regex' => 'قالب :attribute معتبر نیست.',
    'required' => 'وارد کردن :attribute الزامی است.',
    'required_array_keys' => ':attribute باید برای این موارد مقدار داشته باشد: :values.',
    'required_if' => 'وقتی :other برابر :value است، وارد کردن :attribute الزامی است.',
    'required_if_accepted' => 'وقتی :other پذیرفته شده باشد، وارد کردن :attribute الزامی است.',
    'required_if_declined' => 'وقتی :other رد شده باشد، وارد کردن :attribute الزامی است.',
    'required_unless' => 'مگر آنکه :other در :values باشد، وارد کردن :attribute الزامی است.',
    'required_with' => 'وقتی :values موجود است، وارد کردن :attribute الزامی است.',
    'required_with_all' => 'وقتی :values موجود هستند، وارد کردن :attribute الزامی است.',
    'required_without' => 'وقتی :values موجود نیست، وارد کردن :attribute الزامی است.',
    'required_without_all' => 'وقتی هیچ‌کدام از :values موجود نیستند، وارد کردن :attribute الزامی است.',
    'same' => ':attribute باید با :other یکی باشد.',
    'sheba' => 'شماره شبا معتبر نیست؛ دوباره از روی کارت یا اپلیکیشن بانکت بررسی‌ش کن.',
    'size' => [
        'array' => ':attribute باید :size مورد داشته باشد.',
        'file' => 'حجم :attribute باید :size کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر :size باشد.',
        'string' => ':attribute باید :size کاراکتر باشد.',
    ],
    'starts_with' => ':attribute باید با یکی از این موارد شروع شود: :values.',
    'string' => ':attribute باید یک رشته باشد.',
    'timezone' => ':attribute باید یک منطقه‌ی زمانی معتبر باشد.',
    'unique' => 'این :attribute قبلاً ثبت شده است.',
    'uploaded' => 'بارگذاری :attribute ناموفق بود.',
    'uppercase' => ':attribute باید با حروف بزرگ باشد.',
    'url' => ':attribute باید یک نشانی اینترنتی معتبر باشد.',
    'ulid' => ':attribute باید یک ULID معتبر باشد.',
    'uuid' => ':attribute باید یک UUID معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        // A username the app keeps for its own paths is not "invalid" — the shape
        // was fine, the name is simply spoken for.
        'username' => [
            'not_in' => 'این نام کاربری در دسترس نیست.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'amount' => 'مبلغ',
        'bio' => 'درباره‌ی من',
        'current_password' => 'رمز عبور فعلی',
        'description' => 'توضیح',
        'email' => 'ایمیل',
        'name' => 'نام',
        'password' => 'رمز عبور',
        'password_confirmation' => 'تکرار رمز عبور',
        'price' => 'قیمت',
        'purchase_link' => 'لینک محصول',
        'sheba' => 'شماره شبا',
        'thumbnail' => 'تصویر',
        'title' => 'عنوان',
        'token' => 'کد بازیابی',
        'username' => 'نام کاربری',
    ],

];
