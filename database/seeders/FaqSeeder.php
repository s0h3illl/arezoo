<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Seed the questions the landing page answers.
     *
     * Written out rather than faked: this is the copy that ships, and a page of
     * lorem ipsum would tell nobody whether the answers are the right ones. The
     * insertion order is the reading order — the table has no ordering column,
     * so the landing page sorts by identifier.
     *
     * @var list<array{title: string, body: string}>
     */
    private const array QUESTIONS = [
        [
            'title' => 'استفاده از آرزو چقدر هزینه داره؟',
            'body' => 'ساختن لیست، اضافه کردن آرزو و فرستادن لینکش برای بقیه رایگانه. تنها هزینه وقتیه که بخوای پولی رو که جمع شده برداشت کنی؛ اون موقع کارمزد برداشت از مبلغ کم می‌شه و قبل از تأیید بهت نشون داده می‌شه.',
        ],
        [
            'title' => 'برای دیدن لیست یک نفر باید ثبت‌نام کنم؟',
            'body' => 'نه. هر کسی لینک لیست رو داشته باشه می‌تونه بازش کنه و آرزوها رو ببینه. ثبت‌نام فقط برای ساختن لیست خودت و مشارکت توی آرزوی بقیه لازمه.',
        ],
        [
            'title' => 'اگر توی آرزوی کسی مشارکت کنم، اسمم رو می‌بینه؟',
            'body' => 'خودت تصمیم می‌گیری. می‌تونی مشارکتت برای همه پیدا باشه، فقط برای صاحب لیست، یا کاملاً مخفی بمونه. توی حالت مخفی حتی صاحب لیست هم نمی‌فهمه کار تو بوده — ولی پولش سر جاشه و روی آرزو حساب می‌شه.',
        ],
        [
            'title' => 'لیست آرزوهام رو چه کسی می‌بینه؟',
            'body' => 'فقط کسایی که خودت لینک رو براشون فرستادی. تا وقتی لینک رو جایی نذاری، لیست تو در دسترس کسی نیست و ما هیچ‌وقت اطلاعات شخصیت رو در اختیار کس دیگه‌ای نمی‌ذاریم.',
        ],
        [
            'title' => 'از چه فروشگاه‌هایی می‌تونم آرزو اضافه کنم؟',
            'body' => 'از هر فروشگاهی. کافیه لینک صفحه‌ی محصول رو بذاری؛ ما فروشگاه‌ها رو محدود نکردیم. آرزویی هم که اصلاً لینک نداره می‌تونی با یک عنوان و قیمت تقریبی بنویسی.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::QUESTIONS as $question) {
            Faq::query()->create($question);
        }
    }
}
