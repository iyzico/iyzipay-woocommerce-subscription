# iyzico Subscription for WooCommerce

> WooCommerce mağazanızda iyzico altyapısı ile düzenli (abonelik) ödemeleri kolay ve güvenli şekilde alın.

[![WordPress](https://img.shields.io/badge/WordPress-6.6%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## İçindekiler

- [Genel Bakış](#genel-bakış)
- [Özellikler](#özellikler)
- [Sistem Gereksinimleri](#sistem-gereksinimleri)
- [Kurulum](#kurulum)
  - [FTP / Manuel Kurulum](#1-ftp--manuel-kurulum)
  - [Composer ile Geliştirici Kurulumu](#2-composer-ile-geliştirici-kurulumu)
- [Yapılandırma](#yapılandırma)
  - [iyzico API Anahtarlarının Alınması](#iyzico-api-anahtarlarının-alınması)
  - [Eklenti Ayarları](#eklenti-ayarları)
- [Kullanım](#kullanım)
  - [Abonelik Ürünü Oluşturma](#abonelik-ürünü-oluşturma)
  - [Müşteri Tarafı](#müşteri-tarafı)
  - [Yönetici Paneli](#yönetici-paneli)
- [Güvenlik ve PCI-DSS Uyumluluğu](#güvenlik-ve-pci-dss-uyumluluğu)
- [KVKK / GDPR](#kvkk--gdpr)
- [Sıkça Sorulan Sorular](#sıkça-sorulan-sorular)
- [Sorun Giderme](#sorun-giderme)
- [Geliştiriciler İçin](#geliştiriciler-için)
- [Destek](#destek)
- [Lisans](#lisans)

---

## Genel Bakış

**iyzico Subscription for WooCommerce**, WooCommerce mağazanızın iyzico ödeme altyapısı üzerinden düzenli (yinelenen) ödemeler almasını sağlayan bağımsız bir abonelik eklentisidir. **WooCommerce Subscriptions premium eklentisine ihtiyaç duymaz**; kendi abonelik altyapısını sağlar.

Eklenti, iyzico'nun **kart saklama (Tokenization / Stored Card)** özelliğini kullanarak müşterilerinizin ilk ödemede verdikleri kart bilgilerini güvenli bir şekilde saklar ve sonraki dönemlerdeki ödemeleri otomatik olarak tahsil eder.

### Mimari Özet

```
iyzipay-woocommerce-subscription/
├── iyzico-subscription.php        # Ana eklenti dosyası
├── bootstrap.php                  # Container başlatıcı
├── composer.json                  # Composer bağımlılıkları
├── readme.txt                     # WordPress.org readme
├── assets/                        # CSS, JS, kart görselleri
│   ├── css/admin.css
│   ├── img/cards/
│   └── js/frontend/
├── languages/                     # POT + tr_TR.po + en_US.po
├── templates/                     # WC şablon override'ları + e-posta template'leri
│   ├── emails/
│   └── single-product/
└── src/
    ├── Plugin.php                 # Singleton bootstrap
    ├── Admin/                     # Native WP yönetici paneli (WP_List_Table)
    │   ├── SubscriptionAdminController.php
    │   ├── SubscriptionListTable.php   # WP_List_Table extension
    │   └── Views/
    ├── Gateway/                   # WC_Payment_Gateway uygulaması
    ├── Migrations/                # Versiyonlu DB migration sistemi
    ├── Models/                    # Repository / Validator / Calculator / Factory (DI)
    ├── Product/                   # WC_Product_Subscription
    └── Services/                  # İş mantığı (Hook, Renewal, Email, Privacy, vs.)
```

---

## Özellikler

- ✅ **iyzico Hosted Checkout Form** ile güvenli, 3D Secure destekli ilk ödeme — kart bilgileri sunucunuza dokunmaz
- ✅ **Otomatik dönem yenileme** (günlük / haftalık / aylık / yıllık)
- ✅ **Kayıtlı kart (Tokenization)** ile yenilemelerde non-3DS hızlı tahsilat
- ✅ Müşteriye özel **"Aboneliklerim"** ve **"Kayıtlı Kartlarım"** hesap sayfaları
- ✅ Müşteri için: **askıya alma, iptal, yeniden aktifleştirme**
- ✅ Yönetici için **native WordPress List Table** tabanlı yönetim paneli (Posts/Orders ile aynı görünüm)
- ✅ Yönetici tarafında **manuel ödeme tetikleme** (failed renewals için)
- ✅ Detay sayfasında ödeme geçmişi ve sipariş bağlantıları
- ✅ **Bulk actions**: çoklu askıya al / iptal et / yeniden aktifleştir
- ✅ **Filtre & arama**: durum, müşteri, tarih aralığı
- ✅ **WooCommerce HPOS** (Custom Order Tables) uyumlu
- ✅ **WooCommerce Blocks** (Cart & Checkout) uyumlu
- ✅ **Versiyon bazlı migration** sistemi
- ✅ Başarısız ödemeler için **3 deneme** sonrası otomatik askıya alma; askıdaki abonelikler 3 gün sonra yeniden denenir
- ✅ HTML **e-posta bildirimleri** (yeni abonelik, yenileme başarılı/başarısız, iptal, askıya alma, süresi dolma)
- ✅ **Misafir checkout engelleme**: abonelik ürünü için zorunlu giriş + cart/checkout uyarıları
- ✅ **TC kimlik numarası alanı** + algoritmik validasyon (mod-10/mod-11)
- ✅ **KVKK / GDPR** `personal_data_exporter` + `personal_data_eraser` desteği
- ✅ **Multi-currency**: TRY, USD, EUR, GBP, CHF, NOK, RUB, IRR
- ✅ **i18n**: Türkçe & İngilizce dil dosyaları
- ✅ **Akıllı hata kurtarma**: abonelik oluşturulamazsa ödeme otomatik iyzico tarafında iptal edilir
- ✅ Sandbox (test) modu

---

## Sistem Gereksinimleri

| Bileşen | Minimum | Önerilen |
| --- | --- | --- |
| **WordPress** | 6.6 | 6.7+ |
| **WooCommerce** | 8.0 | 9.0+ |
| **PHP** | 7.4 | 8.1+ |
| **MySQL** | 5.7 | 8.0+ |
| **PHP Eklentileri** | `curl`, `json`, `mbstring` | + `intl`, `openssl` |
| **SSL** | **Zorunlu (HTTPS)** | TLS 1.2+ |
| **WP-Cron veya Sistem Cron** | Aktif | **Sistem cron'u şiddetle önerilir** |

> ⚠️ **Önemli:** Düşük trafikli sitelerde WP-Cron, abonelik yenilemelerini geciktirebilir. Hosting sağlayıcınızdan gerçek bir sistem cron'u (örn. `wp-cron.php`'yi 5 dakikada bir tetikleyen) ayarlamanız **şiddetle tavsiye edilir.**

---

## Kurulum

> ℹ️ Eklenti henüz WordPress.org dizininde değildir. Aşağıdaki manuel yöntemlerden birini kullanın.

### 1. FTP / Manuel Kurulum

1. [GitHub Releases](https://github.com/iyzico/iyzipay-woocommerce-subscription/releases) sayfasından son sürümün **vendor klasörü dahil** ZIP dosyasını indirin.
2. ZIP dosyasını `wp-content/plugins/` dizinine açın. Sonuç şu yapıda olmalı:
   ```
   wp-content/plugins/iyzipay-woocommerce-subscription/
   ├── iyzico-subscription.php
   ├── vendor/                      ← bu klasör mutlaka olmalı
   └── ...
   ```
3. WordPress yönetim panelinde **Eklentiler** sayfasına gidin.
4. **iyzico Subscription for WooCommerce** eklentisini **Etkinleştir**.
5. Etkinleştirme sırasında veritabanı tabloları otomatik oluşturulur:
   - `wp_iyzico_subscriptions` — Abonelik kayıtları
   - `wp_iyzico_subscription_notifications` — Gönderilen bildirim kayıtları
   - `wp_iyzico_subscription_payments` — Ödeme deneme logları
   - `wp_iyzico_saved_cards` — Saklı kart token'ları (kart numarası **değil**)

### 2. Composer ile Geliştirici Kurulumu

GitHub'dan klonladıysanız `vendor/` klasörü gelmez; Composer ile bağımlılıkları kurmanız gerekir:

```bash
cd wp-content/plugins/
git clone https://github.com/iyzico/iyzipay-woocommerce-subscription.git
cd iyzipay-woocommerce-subscription
composer install --no-dev -o
```

> ⚠️ `vendor/` klasörü olmadan eklenti **fatal error** verir. Eklentiyi son kullanıcıya dağıtırken `vendor/` klasörünü mutlaka pakete dahil edin.

---

## Yapılandırma

### iyzico API Anahtarlarının Alınması

1. [merchant.iyzipay.com](https://merchant.iyzipay.com/) adresine giriş yapın.
2. Sol menüden **Ayarlar → API Anahtarları** bölümüne gidin.
3. **API Anahtarı** ve **Secret (Gizli) Anahtar** değerlerini kopyalayın.
4. Test ortamı için [sandbox-merchant.iyzipay.com](https://sandbox-merchant.iyzipay.com/) üzerinden ayrı sandbox anahtarları alabilirsiniz.

> 💡 Üretim (live) ortamına geçmeden önce mutlaka sandbox modunda end-to-end testlerinizi tamamlayın.

> 🔑 **Kayıtlı Kart Saklama** (tokenization) iyzico merchant panelinizde **aktif** olmalıdır. Aksi halde abonelik yenilemeleri çalışmaz.

### Eklenti Ayarları

WordPress yönetici panelinde:

**WooCommerce → Ayarlar → Ödemeler → iyzico Abonelik → Yönet**

| Alan | Açıklama |
| --- | --- |
| **Aktif/Pasif** | Ödeme yöntemini ödeme sayfasında göster/gizle |
| **Başlık** | Müşteriye gösterilecek ödeme yöntemi adı |
| **Açıklama** | Ödeme yönteminin altında görünen kısa metin |
| **API Anahtarı** | iyzico panelinden alınan API key |
| **Gizli Anahtar** | iyzico panelinden alınan Secret key (password input ile maskelenir) |
| **Test Modu** | İşaretliyse `sandbox-api.iyzipay.com` kullanılır |

> 🔒 **Güvenlik:** API anahtarları veritabanında düz metin olarak saklanır. Sunucu erişimini ve veritabanı yedeklerini kısıtlayın.

---

## Kullanım

### Abonelik Ürünü Oluşturma

1. **Ürünler → Yeni Ekle**
2. **Ürün verisi** açılır listesinden **`Abonelik`** ürün tipini seçin.
3. **Abonelik Ayarları** sekmesinde aşağıdaki alanları doldurun:
   - **Normal Fiyat:** Her dönem tahsil edilecek tutar (₺)
   - **Abonelik Periyodu:** Günlük / Haftalık / Aylık / Yıllık
   - **Abonelik Süresi:** Toplam fatura döngüsü sayısı
     - `0` = süresiz (manuel iptal edilene kadar)
     - `12` = aylık seçildiyse 12 ay sonra biter
   - **Deneme Süresi (gün):** Ücretsiz deneme süresi (0 = deneme yok)
4. Ürünü **Yayınla**.

### Müşteri Tarafı

#### Ön Koşul: Üye Girişi

Aboneliklerin yönetilebilmesi için müşteri **giriş yapmış olmalıdır**. Misafir kullanıcı sepete abonelik eklerse:
- Cart sayfasında bilgilendirme notice'ı görünür
- Checkout sayfasının üstünde turuncu uyarı kutusu + **Giriş Yap** ve **Hesap Oluştur** butonları gösterilir
- Submit edilirse "Üye girişi yapın" hatasıyla durdurulur

#### TC Kimlik Numarası

Sepetinde abonelik ürünü olan müşteriden checkout'ta **TC kimlik numarası** alanı toplanır (algoritmik olarak doğrulanır). Bu değer kullanıcı meta'sına kaydedilir ve sonraki yenileme ödemelerinde iyzico'ya gönderilir.

#### İlk Satın Alma Akışı

1. Müşteri abonelik ürününü sepete ekler.
2. Giriş yapar (zorunlu) ve checkout'a geçer.
3. Adres bilgileri + **TC Kimlik Numarası** alanlarını doldurur.
4. **iyzico Abonelik** ödeme yöntemini seçer.
5. **Siparişi tamamla** butonuna basar.
6. **iyzico Hosted Checkout Form**'una yönlendirilir → kart bilgilerini iyzico domain'inde girer → 3D Secure doğrulaması yapılır.
7. Başarılı ödeme sonrası:
   - Ödeme tahsil edildi ve sipariş **completed** olarak işaretlenir.
   - Abonelik kaydı oluşturulur.
   - Kart bilgisi (token + cardUserKey) sonraki tahsilatlar için kaydedilir (kart numarası değil — yalnızca iyzico tokenizasyonu).
   - "Aboneliğiniz Oluşturuldu" e-postası gönderilir.

> 🛡️ **Otomatik kurtarma:** Eğer ödeme alındıktan sonra herhangi bir nedenle abonelik kaydı oluşturulamazsa, eklenti **iyzico üzerinde ödemeyi otomatik olarak iptal eder** ve müşteri "Hesabınızdan tahsilat yapılmamıştır" mesajıyla bilgilendirilir. Müşteri "ödedim ama aboneliğim yok" durumunu yaşamaz.

#### Aboneliklerim Sayfası

Müşteri **Hesabım → Aboneliklerim** menüsünden:
- Tüm aboneliklerini listeleyebilir
- Aktif abonelikleri **askıya alabilir** (geçici)
- Abonelikleri **iptal edebilir** (kalıcı)
- Askıdaki abonelikleri **yeniden aktifleştirebilir**

#### Kayıtlı Kartlarım Sayfası

Müşteri **Hesabım → Kayıtlı Kartlarım** menüsünden:
- iyzico'da saklanan kartlarını listeleyebilir (banka, kart tipi, son 4 hane, takma ad)
- Kartlarını silebilir (iyzico API üzerinden gerçek silme)

### Yönetici Paneli

**WooCommerce → iyzico Abonelikler** menüsünden:

#### Liste Sayfası

- **Native WP_List_Table** — Posts/Orders ile aynı görünüm
- **Stat kartları**: Toplam, Aktif, Askıda, İptal, Aylık Gelir
- **Filtreler**: durum dropdown, tarih aralığı, müşteri arama (ad/email)
- **Sortable kolonlar**: ID, durum, tutar, başlangıç, sonraki ödeme
- **Row actions**: Görüntüle, Askıya Al, İptal Et, Yeniden Aktifleştir, Ödeme Tetikle
- **Bulk actions**: Çoklu askıya alma / iptal / yeniden aktifleştirme
- **Screen options**: sayfa başına abonelik sayısı

#### Detay Sayfası

- **Postbox layout** (sol: bilgi + ödeme geçmişi, sağ: durum + işlemler)
- Müşteri / Ürün / Sipariş edit linkleri
- Ödeme geçmişi (iyzico Payment ID, hata mesajları, başarı/başarısızlık)
- Tek tık ile abonelik aksiyon butonları
- **Manuel ödeme tetikle** (failed renewals için)

### Otomatik Yenileme Süreci

1. WP-Cron her saat `iyzico_subscription_renewal_check` event'ini tetikler.
2. `next_payment <= NOW()` ve `status = 'active'` olan abonelikler bulunur.
3. Her biri için iyzico'ya `CreatePaymentRequest` gönderilir (`cardUserKey` + `cardToken` ile).
4. **Başarılı:** `next_payment` ileri tarihe atılır, `completed_cycles` artar, e-posta gider, otomatik yenileme siparişi oluşturulur.
5. **Başarısız:** `failed_payments` artar, **3 başarısız ödeme** sonrası abonelik **suspended** olur.
6. Günlük olarak `iyzico_subscription_retry_failed` cron'u, askıdaki abonelikleri 3 gün sonra yeniden dener (en fazla 5 deneme).

---

## Güvenlik ve PCI-DSS Uyumluluğu

### iyzico Tokenization Modeli

Bu eklenti, **PCI-DSS Level 1** sertifikalı iyzico altyapısını kullanır:

- ✅ Kart numarası, CVV, son kullanma tarihi **hiçbir zaman** sizin sunucunuza gelmez.
- ✅ Müşteri kart bilgilerini **iyzico Hosted Checkout Form**'una girer (iyzico domain'inde — `cpp.iyzipay.com` / `sandbox-cpp.iyzipay.com`).
- ✅ Eklenti yalnızca **kart token'ı** (`cardToken`) ve **kullanıcı anahtarı** (`cardUserKey`) saklar.
- ✅ Bu token değerleri tek başına ödeme yapamaz; iyzico API anahtarları olmadan kullanılamaz.

### Tüccar Olarak PCI-DSS Sorumluluğunuz

iyzico tokenization modelini kullandığınız için sizin yükümlülüğünüz **PCI-DSS SAQ A** (Self-Assessment Questionnaire A) seviyesindedir. Yine de aşağıdaki kontrolleri uygulamanız **gereklidir**:

| Kontrol | Açıklama | Durum |
| --- | --- | --- |
| 🔐 **HTTPS / TLS 1.2+** | Sitenin tamamı SSL ile şifrelenmiş olmalı | **Zorunlu** |
| 🔑 **Güçlü WP yönetici parolaları** | 2FA önerilir (Wordfence, Two Factor) | Zorunlu |
| 🛡️ **API anahtarlarını koru** | `wp_options` tablosuna erişimi sınırla; `wp-config.php` dosya izni `600` | Zorunlu |
| 📝 **Düzenli güncelleme** | WP, WooCommerce, PHP, eklentiler | Zorunlu |
| 🔍 **Güvenlik tarayıcı** | Wordfence / Sucuri / WP Cerber | Önerilir |
| 🚫 **Debug log'ları kapat** | Production'da `WP_DEBUG_LOG = false` | Zorunlu |
| 💾 **Yedekleme** | Veritabanı + dosya yedekleri | Zorunlu |
| 📊 **Erişim logları** | Ödeme/iptal işlemlerini loglayın (audit trail) | Önerilir |

### Eklenti İçi Güvenlik Önlemleri

- ✅ Tüm form gönderimlerinde WordPress nonce doğrulaması (`wp_nonce_url`, `check_admin_referer`)
- ✅ AJAX endpoint'lerinde `check_ajax_referer` + `current_user_can` kontrolü
- ✅ AJAX endpoint'lerinde **sahiplik kontrolü** (kullanıcı yalnızca kendi aboneliği üzerinde işlem yapabilir)
- ✅ SQL sorgularında `$wpdb->prepare()` kullanımı + `orderby` whitelist
- ✅ Çıktıların `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` ile filtrelenmesi
- ✅ `$_GET` / `$_POST` verilerinin `sanitize_text_field`, `wp_unslash`, `absint` ile temizlenmesi
- ✅ `wp_safe_redirect` ile open-redirect koruması
- ✅ Misafir kullanıcı için abonelik checkout'u **4 katmanda engellenir** (cart, checkout-form, registration-required, submit-process)

---

## KVKK / GDPR

Eklenti, WordPress'in `personal_data_exporter` ve `personal_data_eraser` filtrelerini implement eder:

### Veri Dışa Aktarma

**Ayarlar → Gizlilik → Kişisel Verileri Dışa Aktar** menüsünden bir e-posta adresi girildiğinde:
- Kullanıcının tüm aboneliklerinin (id, durum, tutar, periyot, tarihler) bir XLSX raporu oluşturulur.

### Veri Silme

**Ayarlar → Gizlilik → Kişisel Verileri Sil** menüsünden bir e-posta adresi girildiğinde:
- Aktif abonelikler **iptal edilir** (sipariş notu eklenir).
- iyzico'da kayıtlı kart token'ları silinir (gerçek API çağrısı + DB temizlik).
- `_iyzico_card_user_key`, `_iyzico_card_token`, `_iyzico_identity_number`, `_iyzico_last_ip` user meta alanları temizlenir.

### Müşteri İptalinde Otomatik Temizlik

Müşteri kendi aboneliğini iptal ettiğinde, kullanıcının **başka aktif aboneliği yoksa**:
- iyzico'daki kayıtlı kart otomatik olarak silinir.
- DB'deki kart token'ları temizlenir.

---

## Sıkça Sorulan Sorular

<details>
<summary><b>WooCommerce Subscriptions premium eklentisi gerekli mi?</b></summary>

**Hayır.** Bu eklenti tamamen bağımsız çalışır ve kendi abonelik altyapısını (kendi DB tabloları, cron, e-posta, müşteri sayfaları) sağlar.
</details>

<details>
<summary><b>Misafir alışverişi destekleniyor mu?</b></summary>

**Hayır.** Aboneliklerin yenilenmesi, askıya alınması, iptali ve kart yönetimi için müşterinin kayıtlı bir hesabı olmalıdır. Sepete abonelik eklenirse müşteri otomatik olarak Giriş Yap / Hesap Oluştur akışına yönlendirilir.
</details>

<details>
<summary><b>Müşteri kartını değiştirebilir mi?</b></summary>

Müşteri **Hesabım → Kayıtlı Kartlarım** sayfasından mevcut kartını silebilir; bir sonraki abonelik satın almasında yeni kart kaydedilir. Bir abonelik aktifken kart değiştirme akışı yoktur — bu durumda müşteri mevcut aboneliğini iptal edip yeni satın alma yapmalıdır.
</details>

<details>
<summary><b>3D Secure zorunlu mu?</b></summary>

İlk ödeme **iyzico Hosted Checkout Form** üzerinden yapıldığı için 3D Secure akışı iyzico tarafından yönetilir. Yenileme ödemelerinde 3DS yapılmaz (kayıtlı kart token'ı ile non-3DS işlem). Bu, iyzico'nun "kayıtlı kartla ödeme" politikasıyla uyumludur.
</details>

<details>
<summary><b>Birden fazla aboneliği aynı anda satabilir miyim?</b></summary>

Evet. Müşteri aynı sepette birden fazla abonelik ürünü satın alırsa, her biri **ayrı abonelik kaydı** olarak oluşturulur ve her biri kendi periyoduna göre ayrı yenilenir.
</details>

<details>
<summary><b>Deneme süresi (free trial) destekleniyor mu?</b></summary>

Evet. Ürün ekleme ekranında **Abonelik Ayarları → Deneme Süresi (gün)** alanı bulunur. Müşteri deneme süresi boyunca ücretsiz kullanır; ilk yenileme ödemesi deneme süresinin bitiminde otomatik olarak alınır.
</details>

<details>
<summary><b>Hangi para birimleri destekleniyor?</b></summary>

TRY, USD, EUR, GBP, CHF, NOK, RUB, IRR. WooCommerce mağaza para biriminizden bağımsız olarak abonelik kayıtlarındaki `currency` alanı yenileme ödemelerinde kullanılır.
</details>

<details>
<summary><b>Abonelik tutarı değiştirilebilir mi?</b></summary>

Yönetici panelinde aktif aboneliğin tutarını düzenleme arayüzü henüz yoktur. Doğrudan veritabanından `wp_iyzico_subscriptions.amount` alanı güncellenebilir; bir sonraki yenileme ödemesinde yeni tutar kullanılır.
</details>

<details>
<summary><b>Eklenti hangi dilleri destekliyor?</b></summary>

Türkçe ve İngilizce. POT dosyası `languages/iyzico-subscription.pot`'ta bulunur; başka dillere çeviri eklemek için Poedit gibi araçlarla yeni `.po`/`.mo` dosyaları oluşturabilirsiniz.
</details>

<details>
<summary><b>WooCommerce Blocks (Cart & Checkout) destekleniyor mu?</b></summary>

Evet. Blocks Cart ve Checkout sayfalarında ödeme yöntemi olarak görünür. Hata mesajları Blocks `core/notices` store'una doğru context ile dispatch edilir.
</details>

---

## Sorun Giderme

### "Vendor klasörü bulunamadı" / Fatal error

**Sebep:** `composer install` çalıştırılmamış.

**Çözüm:**
```bash
cd wp-content/plugins/iyzipay-woocommerce-subscription
composer install --no-dev -o
```

### Yenileme ödemeleri yapılmıyor

1. WP-Cron çalışıyor mu kontrol edin:
   ```bash
   wp cron event list --format=table
   ```
2. `iyzico_subscription_renewal_check` ve `iyzico_subscription_retry_failed` event'lerinin listede olduğunu doğrulayın.
3. Manuel olarak çalıştırarak test edin:
   ```bash
   wp cron event run iyzico_subscription_renewal_check
   ```
4. Düşük trafikli site için **sistem cron'u** kurun:
   ```cron
   */5 * * * * curl -s https://siteniz.com/wp-cron.php?doing_wp_cron > /dev/null
   ```

### iyzico callback URL'si çağrılmıyor

iyzico'dan gelen `callbackUrl` aşağıdaki formatta olmalıdır:
```
https://siteniz.com/?wc-api=iyzico_subscription
```
Sunucunuzda WAF veya rate-limit kuralı bu URL'yi engellemediğinden emin olun.

### Ödeme tamamlanıyor ama abonelik oluşmuyor

Eklenti bu duruma karşı bir **otomatik kurtarma mekanizması** içerir: ödeme alındıktan sonra abonelik kaydı oluşturulamazsa **iyzico üzerinde ödeme otomatik iptal edilir**. Sipariş `cancelled` durumuna düşer ve müşteri bilgilendirilir.

Eğer otomatik iptal de başarısız olduysa sipariş `on-hold`'a düşer ve manuel müdahale gerekir. Sipariş notlarında detaylı hata mesajları yer alır.

En sık karşılaşılan sebep:
- `cardToken` veya `cardUserKey` iyzico'dan dönmüyor → iyzico Merchant panelinde **"Kayıtlı Kart Saklama"** özelliği aktif olmalı.

### Hata "Cancel Reason 5213"

iyzico cancel API'sine `reason` alanı göndermiyorsanız bu hata alınır. Eklenti bu alanı zaten otomatik olarak gönderir (`other` varsayılan); bu hatayı görüyorsanız composer'ı güncel sürüme alın.

---

## Geliştiriciler İçin

### Action Hook'ları

```php
do_action('iyzico_subscription_activate');                              // Aktivasyon
do_action('iyzico_subscription_deactivate');                            // Deaktivasyon
do_action('iyzico_subscription_created', $subscription);                // Yeni abonelik
do_action('iyzico_subscription_renewal_success', $subscription);        // Yenileme başarılı
do_action('iyzico_subscription_renewal_failed', $subscription, $error); // Yenileme başarısız
do_action('iyzico_subscription_cancelled', $subscription);              // İptal
do_action('iyzico_subscription_suspended', $subscription);              // Askıya alındı
do_action('iyzico_subscription_expiring', $subscription);               // Süresi dolmak üzere
```

### Programatik Abonelik Oluşturma

```php
use Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory;

$service = SubscriptionFactory::createSubscriptionService();

$subscription_id = $service->createSubscription([
    'user_id'                => 42,
    'order_id'               => 1234,
    'product_id'             => 99,
    'iyzico_subscription_id' => 'iyz_sub_xyz',
    'status'                 => 'active',
    'amount'                 => 99.90,
    'currency'               => 'TRY',
    'period'                 => 'month',
    'period_interval'        => 1,
    'start_date'             => current_time('mysql'),
    'next_payment'           => '2026-09-01 00:00:00',
    'payment_method'         => 'iyzico_subscription',
    'billing_cycles'         => 12,
    'trial_days'             => 7, // opsiyonel
]);
```

### Service Container

Tüm servisler tek `SubscriptionFactory` üzerinden lazy-singleton olarak erişilebilir:

```php
SubscriptionFactory::createSubscriptionRepository();
SubscriptionFactory::createSubscriptionService();
SubscriptionFactory::createRenewalService();
SubscriptionFactory::createSavedCardRepository();
SubscriptionFactory::createPrivacyService();
SubscriptionFactory::createCheckoutFieldService();
// ...
```

### Veritabanı Şeması

`wp_iyzico_subscriptions`, `wp_iyzico_saved_cards`, `wp_iyzico_subscription_payments`, `wp_iyzico_subscription_notifications` tabloları için detaylar için bkz. [`src/Migrations/`](src/Migrations/) klasörü.

### Test Kart Bilgileri (Sandbox)

| Kart Numarası | 3DS | Açıklama |
| --- | --- | --- |
| `5528 7900 0000 0008` | ✅ | Mastercard – başarılı |
| `5526 0800 0000 0006` | ✅ | Mastercard – yetersiz bakiye |
| `4543 6000 0000 0008` | ✅ | Visa – başarılı |
| `4111 1111 1111 1129` | ✅ | Visa – fraud check fail |

CVV: `123`, Son kullanma: gelecekte herhangi bir tarih.

---

## Destek

- 🐛 **Hata bildirimi:** [GitHub Issues](https://github.com/iyzico/iyzipay-woocommerce-subscription/issues)
- 📧 **E-posta:** entegrasyon@iyzico.com
- 📚 **iyzico Dokümantasyonu:** [docs.iyzico.com](https://docs.iyzico.com/)
- 💬 **iyzico Destek:** [iyzico.com/destek](https://www.iyzico.com/destek)

---

## Lisans

Bu eklenti **GNU General Public License v2.0 veya sonrası** ile lisanslanmıştır.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
```

Detaylar için [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) dosyasına bakınız.

---

## Yazar

**iyzico** — Türkiye'nin lider ödeme altyapısı sağlayıcısı

[![iyzico](https://img.shields.io/badge/iyzico-Resmi%20Site-FF6B00.svg)](https://www.iyzico.com/)

© 2026 iyzico. Tüm hakları saklıdır.
