<?php

/**
 * The language codes class provides support for language codes in phpMyFAQ translations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Language;

class LanguageCodes
{
    /**
     * ISO 639 language code list with native language names
     *
     * @var array<string, string>
     */
    protected static array $languageCodes = [
        'aa' => 'Qafar',
        'ab' => 'Аԥсуа бызшәа',
        'af' => 'Afrikaans',
        'am' => 'አማርኛ',
        'ar' => 'العربية',
        'ar_ae' => 'العربية (الإمارات)',
        'ar_bh' => 'العربية (البحرين)',
        'ar_dz' => 'العربية (الجزائر)',
        'ar_eg' => 'العربية (مصر)',
        'ar_iq' => 'العربية (العراق)',
        'ar_jo' => 'العربية (الأردن)',
        'ar_kw' => 'العربية (الكويت)',
        'ar_lb' => 'العربية (لبنان)',
        'ar_ly' => 'العربية (ليبيا)',
        'ar_ma' => 'العربية (المغرب)',
        'ar_om' => 'العربية (عمان)',
        'ar_qa' => 'العربية (قطر)',
        'ar_sa' => 'العربية (السعودية)',
        'ar_sy' => 'العربية (سوريا)',
        'ar_tn' => 'العربية (تونس)',
        'ar_ye' => 'العربية (اليمن)',
        'as' => 'অসমীয়া',
        'ay' => 'Aymar aru',
        'az' => 'Azərbaycan dili',
        'ba' => 'Башҡорт теле',
        'be' => 'Беларуская',
        'bg' => 'Български',
        'bh' => 'भोजपुरी',
        'bi' => 'Bislama',
        'bn' => 'বাংলা',
        'bo' => 'བོད་ཡིག',
        'br' => 'Brezhoneg',
        'bs' => 'Bosanski',
        'ca' => 'Català',
        'co' => 'Corsu',
        'cs' => 'Čeština',
        'cy' => 'Cymraeg',
        'da' => 'Dansk',
        'de' => 'Deutsch',
        'de_at' => 'Deutsch (Österreich)',
        'de_ch' => 'Deutsch (Schweiz)',
        'de_li' => 'Deutsch (Liechtenstein)',
        'de_lu' => 'Deutsch (Luxemburg)',
        'div' => 'ދިވެހި',
        'dz' => 'རྫོང་ཁ',
        'el' => 'Ελληνικά',
        'en' => 'English',
        'en_au' => 'English (Australia)',
        'en_bz' => 'English (Belize)',
        'en_ca' => 'English (Canada)',
        'en_gb' => 'English (United Kingdom)',
        'en_ie' => 'English (Ireland)',
        'en_jm' => 'English (Jamaica)',
        'en_nz' => 'English (New Zealand)',
        'en_ph' => 'English (Philippines)',
        'en_tt' => 'English (Trinidad)',
        'en_us' => 'English (United States)',
        'en_za' => 'English (South Africa)',
        'en_zw' => 'English (Zimbabwe)',
        'eo' => 'Esperanto',
        'es' => 'Español',
        'es_ar' => 'Español (Argentina)',
        'es_bo' => 'Español (Bolivia)',
        'es_cl' => 'Español (Chile)',
        'es_co' => 'Español (Colombia)',
        'es_cr' => 'Español (Costa Rica)',
        'es_do' => 'Español (República Dominicana)',
        'es_ec' => 'Español (Ecuador)',
        'es_es' => 'Español (España)',
        'es_gt' => 'Español (Guatemala)',
        'es_hn' => 'Español (Honduras)',
        'es_mx' => 'Español (México)',
        'es_ni' => 'Español (Nicaragua)',
        'es_pa' => 'Español (Panamá)',
        'es_pe' => 'Español (Perú)',
        'es_pr' => 'Español (Puerto Rico)',
        'es_py' => 'Español (Paraguay)',
        'es_sv' => 'Español (El Salvador)',
        'es_us' => 'Español (Estados Unidos)',
        'es_uy' => 'Español (Uruguay)',
        'es_ve' => 'Español (Venezuela)',
        'et' => 'Eesti',
        'eu' => 'Euskara',
        'fa' => 'فارسی',
        'fi' => 'Suomi',
        'fj' => 'Vosa Vakaviti',
        'fo' => 'Føroyskt',
        'fr' => 'Français',
        'fr_be' => 'Français (Belgique)',
        'fr_ca' => 'Français (Canada)',
        'fr_ch' => 'Français (Suisse)',
        'fr_lu' => 'Français (Luxembourg)',
        'fr_mc' => 'Français (Monaco)',
        'fy' => 'Frysk',
        'ga' => 'Gaeilge',
        'gd' => 'Gàidhlig',
        'gl' => 'Galego',
        'gn' => "Avañe'ẽ",
        'gu' => 'ગુજરાતી',
        'ha' => 'Hausa',
        'he' => 'עברית',
        'hi' => 'हिन्दी',
        'hr' => 'Hrvatski',
        'hu' => 'Magyar',
        'hy' => 'Հայերեն',
        'ia' => 'Interlingua',
        'id' => 'Bahasa Indonesia',
        'ie' => 'Interlingue',
        'ik' => 'Iñupiaq',
        'in' => 'Bahasa Indonesia',
        'is' => 'Íslenska',
        'it' => 'Italiano',
        'it_ch' => 'Italiano (Svizzera)',
        'iw' => 'עברית',
        'ja' => '日本語',
        'ji' => 'ייִדיש',
        'jw' => 'Basa Jawa',
        'ka' => 'ქართული',
        'kk' => 'Қазақ тілі',
        'kl' => 'Kalaallisut',
        'km' => 'ភាសាខ្មែរ',
        'kn' => 'ಕನ್ನಡ',
        'ko' => '한국어',
        'kok' => 'कोंकणी',
        'ks' => 'कश्मिरी',
        'ku' => 'Kurdî',
        'ky' => 'Кыргызча',
        'kz' => 'Кыргызча',
        'la' => 'Latina',
        'ln' => 'Lingála',
        'lo' => 'ລາວ',
        'ls' => 'Slovenščina',
        'lt' => 'Lietuvių',
        'lv' => 'Latviešu',
        'mg' => 'Malagasy',
        'mi' => 'Te Reo Māori',
        'mk' => 'Македонски',
        'ml' => 'മലയാളം',
        'mn' => 'Монгол',
        'mo' => 'Moldovenească',
        'mr' => 'मराठी',
        'ms' => 'Bahasa Melayu',
        'mt' => 'Malti',
        'my' => 'ဗမာစာ',
        'na' => 'Dorerin Naoero',
        'nb' => 'Norsk (Bokmål)',
        'nb_no' => 'Norsk (Bokmål)',
        'ne' => 'नेपाली',
        'nl' => 'Nederlands',
        'nl_be' => 'Nederlands (België)',
        'nn_no' => 'Norsk (Nynorsk)',
        'no' => 'Norsk (Bokmål)',
        'oc' => 'Occitan',
        'om' => 'Oromoo',
        'or' => 'ଓଡ଼ିଆ',
        'pa' => 'ਪੰਜਾਬੀ',
        'pl' => 'Polski',
        'ps' => 'پښتو',
        'pt' => 'Português',
        'pt_br' => 'Português (Brasil)',
        'qu' => 'Runa Simi',
        'rm' => 'Rumantsch',
        'rn' => 'Ikirundi',
        'ro' => 'Română',
        'ro_md' => 'Română (Moldova)',
        'ru' => 'Русский',
        'ru_md' => 'Русский (Молдова)',
        'rw' => 'Kinyarwanda',
        'sa' => 'संस्कृतम्',
        'sb' => 'Serbšćina',
        'sd' => 'سنڌي',
        'sg' => 'Sängö',
        'sh' => 'Srpskohrvatski',
        'si' => 'සිංහල',
        'sk' => 'Slovenčina',
        'sl' => 'Slovenščina',
        'sm' => "Gagana fa'a Samoa",
        'sn' => 'chiShona',
        'so' => 'Soomaali',
        'sq' => 'Shqip',
        'sr' => 'Српски',
        'ss' => 'SiSwati',
        'st' => 'Sesotho',
        'su' => 'Basa Sunda',
        'sv' => 'Svenska',
        'sv_fi' => 'Svenska (Finland)',
        'sw' => 'Kiswahili',
        'sx' => 'Sesotho',
        'syr' => 'ܣܘܪܝܝܐ',
        'ta' => 'தமிழ்',
        'te' => 'తెలుగు',
        'tg' => 'Тоҷикӣ',
        'th' => 'ไทย',
        'ti' => 'ትግርኛ',
        'tk' => 'Türkmen',
        'tl' => 'Tagalog',
        'tn' => 'Setswana',
        'to' => 'Lea faka-Tonga',
        'tr' => 'Türkçe',
        'ts' => 'Xitsonga',
        'tt' => 'Татар',
        'tw' => 'Twi',
        'uk' => 'Українська',
        'ur' => 'اردو',
        'uz' => 'Oʻzbekcha',
        'vi' => 'Tiếng Việt',
        'vo' => 'Volapük',
        'wo' => 'Wolof',
        'xh' => 'isiXhosa',
        'yi' => 'ייִדיש',
        'yo' => 'Yorùbá',
        'zh' => '中文',
        'zh_cn' => '中文 (中国)',
        'zh_hk' => '中文 (香港)',
        'zh_mo' => '中文 (澳門)',
        'zh_sg' => '中文 (新加坡)',
        'zh_tw' => '中文 (台灣)',
        'zu' => 'isiZulu',
    ];

    /**
     * phpMyFAQ supported language code list, this is a representation of phpMyFAQ translations
     *
     * @var array|string[]
     */
    protected static array $supportedLanguageCodes = [
        'ar' => 'العربية',
        'eu' => 'Euskara',
        'bn' => 'বাংলা',
        'bs' => 'Bosanski',
        'zh' => '中文',
        'cs' => 'Čeština',
        'da' => 'Dansk',
        'nl' => 'Nederlands',
        'en' => 'English',
        'fa' => 'فارسی',
        'fi' => 'Suomi',
        'fr' => 'Français',
        'fr_ca' => 'Français (Canada)',
        'de' => 'Deutsch',
        'el' => 'Ελληνικά',
        'he' => 'עברית',
        'hi' => 'हिन्दी',
        'hu' => 'Magyar',
        'id' => 'Bahasa Indonesia',
        'it' => 'Italiano',
        'ja' => '日本語',
        'ko' => '한국어',
        'lv' => 'Latviešu',
        'lt' => 'Lietuvių',
        'ms' => 'Bahasa Melayu',
        'mn' => 'Монгол',
        'nb' => 'Norsk (Bokmål)',
        'pl' => 'Polski',
        'pt' => 'Português',
        'pt_br' => 'Português (Brasil)',
        'ro' => 'Română',
        'ru' => 'Русский',
        'sr' => 'Српски',
        'sk' => 'Slovenčina',
        'sl' => 'Slovenščina',
        'es' => 'Español',
        'sv' => 'Svenska',
        'th' => 'ไทย',
        'tr' => 'Türkçe',
        'zh_tw' => '中文 (台灣)',
        'uk' => 'Українська',
        'ur' => 'اردو',
        'vi' => 'Tiếng Việt',
        'cy' => 'Cymraeg',
    ];

    /**
     * Returns all language codes.
     *
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        return static::$languageCodes;
    }

    public static function getAllSorted(): array
    {
        $sortedLanguageCodes = static::$languageCodes;
        asort($sortedLanguageCodes);
        return $sortedLanguageCodes;
    }

    /**
     * Return language name from language code.
     *
     * @return string|null → language code or null
     */
    public static function get(string $key): ?string
    {
        return static::$languageCodes[strtolower($key)] ?? null;
    }

    /**
     * Return language name from a phpMyFAQ supported language code.
     *
     * @return string|null → language code or null
     */
    public static function getSupported(string $key): ?string
    {
        return static::$supportedLanguageCodes[strtolower($key)] ?? null;
    }

    public static function getKey(string $value): false|int|string
    {
        return array_search($value, static::$languageCodes, true);
    }

    public static function getAllSupported(): array
    {
        return static::$supportedLanguageCodes;
    }
}
