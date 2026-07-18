# منتقي التاريخ الهجري (أم القرى) لـ Filament

مكوّن اختيار تاريخ (وتاريخ ووقت) هجري لواجهات [Filament](https://filamentphp.com) على تقويم أم القرى الرسمي، يعرض للمستخدم **التاريخين الهجري والميلادي معًا أثناء الاختيار**، مع تحكم مستقل في تقويم العرض وتقويم التخزين.

التحويلات مبنية على حزمة [`asimnet/ummulqura-calendar`](https://github.com/AsimNet/ummulqura-calendar) المستخرجة من بيانات مركز الملك عبدالعزيز للعلوم والتقنية (KACST) الرسمية، وليست تقريبًا حسابيًا.

## المزايا

- شبكة شهور هجرية حقيقية (29/30 يومًا من الجداول الرسمية).
- محوران مستقلان: تقويم العرض وتقويم التخزين، بأي تركيبة.
- التاريخ المكافئ ظاهر دائمًا في حقل الإدخال وفي أسفل لوحة الاختيار.
- دعم الوقت (ساعات/دقائق/ثوانٍ) في الإصدار الأول.
- تحقق خادمي من صحة التاريخ الهجري (يرفض مثلًا 30 صفر) برسائل قابلة للترجمة.
- يرث `DateTimePicker` من Filament نفسه، فكل الخيارات المألوفة تعمل.
- دعم RTL والوضع الداكن والأرقام العربية المشرقية (٠-٩).
- النطاق المدعوم: **1 محرم 1318هـ → 30 ذو الحجة 1500هـ**.

## التثبيت

```bash
composer require asimnet/filament-ummulqura-date-picker
php artisan filament:assets
```

## الاستخدام

```php
use AsimNet\FilamentUmmulqura\Forms\Components\HijriDatePicker;
use AsimNet\FilamentUmmulqura\Enums\CalendarSystem;

// العرض هجري والتخزين ميلادي (الافتراضي)
HijriDatePicker::make('birth_date');

// العرض ميلادي والتخزين هجري
HijriDatePicker::make('hijri_date')
    ->displayCalendar(CalendarSystem::Gregorian)
    ->storageCalendar(CalendarSystem::Hijri);
```

راجع [README.md](README.md) للتوثيق الكامل بجميع الخيارات.

## الترخيص

MIT — انظر [LICENSE](LICENSE).
