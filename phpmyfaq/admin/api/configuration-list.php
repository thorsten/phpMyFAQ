<?php

/**
 * Private phpMyFAQ Admin API: lists the complete configuration items as text/html.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-26
 */

use Abraham\TwitterOAuth\TwitterOAuth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\AdministrationHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\PermissionHelper;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if (!empty($_SESSION['access_token'])) {
    $connection = new TwitterOAuth(
        $faqConfig->get('socialnetworks.twitterConsumerKey'),
        $faqConfig->get('socialnetworks.twitterConsumerSecret'),
        $_SESSION['access_token']['oauth_token'],
        $_SESSION['access_token']['oauth_token_secret']
    );

    $content = $connection->get('account/verify_credentials');
}

$request = Request::createFromGlobals();
$configMode = Filter::filterVar($request->query->get('conf'), FILTER_SANITIZE_SPECIAL_CHARS, 'main');

/**
 * @param mixed  $key
 * @param string $type
 */
function renderInputForm(mixed $key, string $type): void
{
    $faqConfig = Configuration::getConfigurationInstance();

    switch ($type) {
        case 'area':
            printf(
                '<textarea name="edit[%s]" rows="4" class="form-control">%s</textarea>',
                $key,
                str_replace('<', '&lt;', str_replace('>', '&gt;', $faqConfig->get($key)))
            );
            printf("</div>\n");
            break;

        case 'input':
            if (
                '' === $faqConfig->get($key) && 'socialnetworks.twitterAccessTokenKey' == $key &&
                isset($_SESSION['access_token'])
            ) {
                $value = $_SESSION['access_token']['oauth_token'];
            } elseif (
                '' === $faqConfig->get($key) && 'socialnetworks.twitterAccessTokenSecret' == $key &&
                isset($_SESSION['access_token'])
            ) {
                $value = $_SESSION['access_token']['oauth_token_secret'];
            } else {
                $value = str_replace('"', '&quot;', $faqConfig->get($key) ?? '');
            }
            echo '<div class="input-group">';

            switch ($key) {
                case 'main.administrationMail':
                    $type = 'email';
                    break;
                case 'main.referenceURL':
                case 'main.privacyURL':
                    $type = 'url';
                    break;
                default:
                    $type = 'text';
                    break;
            }

            printf(
                '<input class="form-control" type="%s" name="edit[%s]" id="edit[%s]" value="%s" step="1" min="0">',
                is_numeric($value) ? 'number' : $type,
                $key,
                $key,
                $value
            );

            if ('api.apiClientToken' === $key) {
                echo '<div class="input-group-append">';
                echo '<button class="btn btn-dark" id="pmf-generate-api-token" type="button" onclick="generateApiToken()">Generate API Client Token</button>';
                echo '</div>';
                ?>
                <script>
                  try {
                    const generateUUID = () => {
                      let date = new Date().getTime();

                      if (window.performance && typeof window.performance.now === 'function') {
                        date += performance.now();
                      }

                      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
                        const random = (date + Math.random() * 16) % 16 | 0;
                        date = Math.floor(date / 16);
                        return (char === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
                      });
                    }

                    const buttonGenerateApiToken = document.getElementById('pmf-generate-api-token');
                    const inputConfigurationApiToken = document.getElementById('edit[api.apiClientToken]');

                    if (buttonGenerateApiToken) {
                      if (inputConfigurationApiToken.value !== '') {
                        buttonGenerateApiToken.disabled = true;
                      }
                      buttonGenerateApiToken.addEventListener('click', (event) => {
                        event.preventDefault();
                        inputConfigurationApiToken.value = generateUUID();
                      });
                    }
                  } catch (e) {
                    // do nothing
                  }
                </script>
                <?php
            }
            echo '</div></div>';
            break;

        case 'password':
            printf(
                '<input class="form-control" type="password" autocomplete="off" name="edit[%s]" value="%s">',
                $key,
                $faqConfig->get($key)
            );
            echo "</div>\n";
            break;

        case 'select':
            printf('<select name="edit[%s]" class="form-select">', $key);

            switch ($key) {
                case 'main.language':
                    $languages = LanguageHelper::getAvailableLanguages();
                    if (count($languages) > 0) {
                        echo LanguageHelper::renderLanguageOptions(
                            str_replace(
                                [ 'language_', '.php', ],
                                '',
                                $faqConfig->get('main.language')
                            ),
                            false,
                            true
                        );
                    } else {
                        echo '<option value="language_en.php">English</option>';
                    }
                    break;

                case 'records.orderby':
                    echo AdministrationHelper::sortingOptions($faqConfig->get($key));
                    break;

                case 'records.sortby':
                    printf(
                        '<option value="DESC" %s>%s</option>',
                        ('DESC' == $faqConfig->get($key)) ? 'selected' : '',
                        Translation::get('ad_conf_desc')
                    );
                    printf(
                        '<option value="ASC" %s>%s</option>',
                        ('ASC' == $faqConfig->get($key)) ? 'selected' : '',
                        Translation::get('ad_conf_asc')
                    );
                    break;

                case 'security.permLevel':
                    echo PermissionHelper::permOptions($faqConfig->get($key));
                    break;

                case 'main.templateSet':
                    $faqSystem = new System();
                    $templates = $faqSystem->getAvailableTemplates();

                    foreach ($templates as $template => $selected) {
                        printf(
                            '<option%s>%s</option>',
                            ($selected === true ? ' selected' : ''),
                            $template
                        );
                    }
                    break;

                case 'records.attachmentsStorageType':
                    foreach (Translation::get('att_storage_type') as $i => $item) {
                        $selected = (int)$faqConfig->get($key) === $i ? ' selected' : '';
                        printf('<option value="%d"%s>%s</option>', $i, $selected, $item);
                    }
                    break;

                case 'records.orderingPopularFaqs':
                    printf(
                        '<option value="visits"%s>%s</option>',
                        ('visits' === $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('records.orderingPopularFaqs.visits')
                    );
                    printf(
                        '<option value="voting"%s>%s</option>',
                        ('voting' === $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('records.orderingPopularFaqs.voting')
                    );
                    break;

                case 'search.relevance':
                    printf(
                        '<option value="thema,content,keywords"%s>%s</option>',
                        ('thema,content,keywords' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.thema-content-keywords')
                    );
                    printf(
                        '<option value="thema,keywords,content"%s>%s</option>',
                        (
                            'thema,keywords,content' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.thema-keywords-content')
                    );
                    printf(
                        '<option value="content,thema,keywords"%s>%s</option>',
                        ('content,thema,keywords' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.content-thema-keywords')
                    );
                    printf(
                        '<option value="content,keywords,thema"%s>%s</option>',
                        ('content,keywords,thema' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.content-keywords-thema')
                    );
                    printf(
                        '<option value="keywords,content,thema"%s>%s</option>',
                        ('keywords,content,thema' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.keywords-content-thema')
                    );
                    printf(
                        '<option value="keywords,thema,content"%s>%s</option>',
                        ('keywords,thema,content' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.keywords-thema-content')
                    );
                    break;

                case 'seo.metaTagsHome':
                case 'seo.metaTagsFaqs':
                case 'seo.metaTagsCategories':
                case 'seo.metaTagsPages':
                case 'seo.metaTagsAdmin':
                    $adminHelper = new AdministrationHelper();
                    echo $adminHelper->renderMetaRobotsDropdown($faqConfig->get($key));
                    break;
            }

            echo "</select>\n</div>\n";
            break;

        case 'checkbox':
            printf(
                '<div class="form-check"><input class="form-check-input" type="checkbox" name="edit[%s]" value="true"',
                $key
            );
            if ($faqConfig->get($key)) {
                echo ' checked';
            }
            if ('ldap.ldapSupport' === $key && !extension_loaded('ldap')) {
                echo ' disabled';
            }
            if ('security.useSslForLogins' === $key && !Request::createFromGlobals()->isSecure()) {
                echo ' disabled';
            }
            if ('security.useSslOnly' === $key && !Request::createFromGlobals()->isSecure()) {
                echo ' disabled';
            }
            if ('security.ssoSupport' === $key && !Request::createFromGlobals()->server->get('REMOTE_USER')) {
                echo ' disabled';
            }
            echo '></div></div>';
            break;

        case 'print':
            printf(
                '<input type="text" readonly name="edit[%s]" class="form-control-plaintext" value="%s"></div>',
                $key,
                str_replace('"', '&quot;', $faqConfig->get($key))
            );
            break;

        case 'button':
            printf(
                '<button type="button" class="btn btn-primary" id="btn-phpmyfaq-%s" onclick="handleSendTestMail()">%s</button></div>',
                str_replace('.', '-', $key),
                Translation::get($key)
            );
            break;
    }
}

header('Content-type: text/html; charset=utf-8');

Utils::moveToTop($LANG_CONF, 'main.maintenanceMode');

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {
        if ('socialnetworks.twitterConsumerKey' == $key) {
            echo '<div class="row mb-2"><label class="col-form-label col-lg-3"></label>';
            echo '<div class="col-lg-9">';
            if (
                '' == $faqConfig->get('socialnetworks.twitterConsumerKey') ||
                '' == $faqConfig->get('socialnetworks.twitterConsumerSecret')
            ) {
                echo '<a target="_blank" href="https://dev.twitter.com/apps/new">Create Twitter App for your FAQ</a>';
                echo "<br>\n";
                echo 'Your Callback URL is: ' . $faqConfig->getDefaultUrl() . 'services/twitter/callback.php';
            }

            if (!isset($content)) {
                echo '<br><a target="_blank" href="../../services/twitter/redirect.php">';
                echo '<img src="../../assets/img/twitter.signin.png" alt="Sign in with Twitter"/></a>';
            } else {
                echo $content->screen_name . "<br>\n";
                echo "<img alt=\"Twitter profile\" src='" . $content->profile_image_url_https . "'><br>\n";
                echo 'Follower: ' . $content->followers_count . "<br>\n";
                echo 'Status Count: ' . $content->statuses_count . "<br>\n";
                echo 'Status: ' . $content->status->text;
            }
            echo '</div></div>';
        }

        printf(
            '<div class="row mb-2"><label class="col-lg-3 col-form-label %s">',
            $value[0] === 'checkbox' || $value[0] === 'radio' ? 'pt-0' : ''
        );

        switch ($key) {
            case 'records.maxAttachmentSize':
                printf($value[1], ini_get('upload_max_filesize'));
                break;
            case 'main.dateFormat':
                printf(
                    '<a target="_blank" href="https://www.php.net/manual/%s/function.date.php">%s</a>',
                    $faqLangCode,
                    $value[1]
                );
                break;
            default:
                echo $value[1];
                break;
        }
        ?>
      </label>
      <div class="col-lg-9">
          <?php renderInputForm($key, $value[0]); ?>
      </div>
        <?php
    }
}
