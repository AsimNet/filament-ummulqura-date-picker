# منتقي التاريخ الهجري (أم القرى) لـ Filament

مكوّن اختيار تاريخ (وتاريخ ووقت) هجري لواجهات [Filament](https://filamentphp.com) على تقويم أم القرى الرسمي، يعرض للمستخدم **التاريخين الهجري والميلادي معًا أثناء الاختيار**، مع تحكم مستقل في تقويم العرض وتقويم التخزين.

التحويلات مبنية على حزمة [`asimnet/ummulqura-calendar`](https://github.com/AsimNet/ummulqura-calendar) المستخرجة من بيانات مركز الملك عبدالعزيز للعلوم والتقنية (KACST) الرسمية، وليست تقريبًا حسابيًا.

<p align="center">
  <img
    src="https://raw.githubusercontent.com/AsimNet/filament-ummulqura-date-picker/main/art/demo.gif"
    alt="اختيار تاريخ هجري: تعرض اللوحة شبكة شهر أم القرى الحقيقية مع مقابله الميلادي أسفلها، ثم يُغيَّر الشهر ويُختار يوم، وتُقلب الشبكة إلى الميلادي، ويُضبط الوقت"
    width="640">
</p>

## المزايا

- شبكة شهور هجرية حقيقية (29/30 يومًا من الجداول الرسمية).
- محوران مستقلان: تقويم العرض وتقويم التخزين، بأي تركيبة.
- التاريخ المكافئ ظاهر دائمًا في حقل الإدخال وفي أسفل لوحة الاختيار.
- دعم الوقت (ساعات/دقائق/ثوانٍ) في الإصدار الأول.
- تحقق خادمي من صحة التاريخ الهجري (يرفض مثلًا 30 صفر) برسائل قابلة للترجمة.
- يرث `DateTimePicker` من Filament نفسه، فكل الخيارات المألوفة تعمل.
- دعم RTL والوضع الداكن والأرقام العربية المشرقية (٠-٩).
- النطاق المدعوم: **1 محرم 1318هـ → 30 ذو الحجة 1500هـ**.

<p align="center">
  <img
    src="https://raw.githubusercontent.com/AsimNet/filament-ummulqura-date-picker/main/art/picker-ar-dark.png"
    alt="النموذج بالعربية من اليمين إلى اليسار وفي الوضع الداكن، وبأرقام عربية مشرقية في شبكة الأيام"
    width="900">
</p>

<p align="center"><sub>عربي، اتجاه من اليمين لليسار، وضع داكن، وأرقام عربية مشرقية — الحقول الأربعة في نموذج واحد.</sub></p>

| الشبكة الهجرية والمقابل الميلادي أسفلها | الحقل نفسه بعد قلبه إلى الميلادي أثناء الاختيار |
| --- | --- |
| <img src="https://raw.githubusercontent.com/AsimNet/filament-ummulqura-date-picker/main/art/picker-en-light.png" alt="حقل تاريخ ميلاد مفتوح على شوال 1415، وفي أسفل اللوحة: 24 شوال 1415هـ = 25 مارس 1995"> | <img src="https://raw.githubusercontent.com/AsimNet/filament-ummulqura-date-picker/main/art/picker-gregorian.png" alt="الحقل نفسه يعرض شبكة مارس 1995، وفي أسفل اللوحة: 25 مارس 1995 = 24 شوال 1415هـ"> |

## مطابقة الهوية الوطنية والسجلات المدنية

يعتمد التحويل تقويم أم القرى **كما نُشر في حينه** (`Reckoning::AsPublished` في المكتبة الأساسية). ولا يؤثر ذلك إلا على التواريخ قبل **1420هـ (أبريل 1999م)**: إذ تطبّق خدمة كاكست الرسمية اليوم المعيار الحالي بأثر رجعي، فقد تختلف يومًا عمّا طُبع فعليًا في التقويم — والمطبوع هو ما سُجّل في الهوية الوطنية ووثائق الأحوال المدنية. يستخدم المنتقي القيمة المطبوعة، فيطابق تاريخ الميلاد ما في الهوية عند التحويل ذهابًا وإيابًا.

ومن 1420هـ فصاعدًا تتطابق القراءتان، فلا تتأثر تواريخ اليوم والمستقبل. أمّا الأعوام 1318–1355هـ (قبل 1937م)، حيث لم تستقر تقاويم تلك الحقبة، فيرجع المنتقي إلى إعادة البناء الرسمية.

## التثبيت

تُثبَّت الإضافة ومحرّك التقويم الذي تعتمد عليه مباشرةً من GitHub. أضِف المستودعين إلى Composer (لا يقرأ Composer
المستودعات المعرّفة داخل الحزم التابعة، لذا يحتاج محرّك التقويم إلى سطر خاص به)، ثم اطلب الإضافة:

```bash
composer config repositories.ummulqura-calendar vcs https://github.com/AsimNet/ummulqura-calendar
composer config repositories.filament-ummulqura-date-picker vcs https://github.com/AsimNet/filament-ummulqura-date-picker
composer require asimnet/filament-ummulqura-date-picker
php artisan filament:assets
```

> **حدود GitHub:** يقرأ Composer الإصدارات عبر واجهة GitHub البرمجية. إذا تعثّر التثبيت بسبب الحدود (وهذا شائع في CI)،
> فأضِف رمز GitHub: `composer config --global github-oauth.github.com YOUR-GITHUB-TOKEN` (لا يحتاج أي صلاحيات لمستودع عام)،
> أو مرّره عبر `COMPOSER_AUTH='{"github-oauth":{"github.com":"YOUR-GITHUB-TOKEN"}}'`.

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
