<?php

/**
 * Dialect-agnostic database schema definition for phpMyFAQ.
 *
 * Single source of truth for the entire database schema. Each method returns
 * a configured TableBuilder instance for one table.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Setup\Migration\QueryBuilder\DialectInterface;
use phpMyFAQ\Setup\Migration\QueryBuilder\TableBuilder;

class DatabaseSchema
{
    public function __construct(
        private readonly DialectInterface $dialect,
    ) {
    }

    /**
     * Returns all table definitions in creation order.
     *
     * @return array<string, TableBuilder>
     */
    public function getAllTables(): array
    {
        return [
            'faqadminlog' => $this->adminLog(),
            'faqattachment' => $this->attachment(),
            'faqattachment_file' => $this->attachmentFile(),
            'faqbackup' => $this->backup(),
            'faqbookmarks' => $this->faqbookmarks(),
            'faqcaptcha' => $this->faqcaptcha(),
            'faqcategories' => $this->faqcategories(),
            'faqcategory_news' => $this->faqcategoryNews(),
            'faqcategoryrelations' => $this->faqcategoryrelations(),
            'faqcategory_group' => $this->faqcategoryGroup(),
            'faqcategory_user' => $this->faqcategoryUser(),
            'faqcategory_order' => $this->faqcategoryOrder(),
            'faqchanges' => $this->faqchanges(),
            'faqcomments' => $this->faqcomments(),
            'faqconfig' => $this->faqconfig(),
            'faqdata' => $this->faqdata(),
            'faqdata_revisions' => $this->faqdataRevisions(),
            'faqdata_group' => $this->faqdataGroup(),
            'faqdata_tags' => $this->faqdataTags(),
            'faqdata_user' => $this->faqdataUser(),
            'faqforms' => $this->faqforms(),
            'faqglossary' => $this->faqglossary(),
            'faqgroup' => $this->faqgroup(),
            'faqgroup_right' => $this->faqgroupRight(),
            'faqapi_keys' => $this->faqapiKeys(),
            'faqoauth_clients' => $this->faqoauthClients(),
            'faqoauth_scopes' => $this->faqoauthScopes(),
            'faqoauth_access_tokens' => $this->faqoauthAccessTokens(),
            'faqoauth_refresh_tokens' => $this->faqoauthRefreshTokens(),
            'faqoauth_auth_codes' => $this->faqoauthAuthCodes(),
            'faqinstances' => $this->faqinstances(),
            'faqinstances_config' => $this->faqinstancesConfig(),
            'faqnews' => $this->faqnews(),
            'faqcustompages' => $this->faqcustompages(),
            'faqquestions' => $this->faqquestions(),
            'faqright' => $this->faqright(),
            'faqsearches' => $this->faqsearches(),
            'faqseo' => $this->faqseo(),
            'faqsessions' => $this->faqsessions(),
            'faqstopwords' => $this->faqstopwords(),
            'faqtags' => $this->faqtags(),
            'faquser' => $this->faquser(),
            'faquserdata' => $this->faquserdata(),
            'faquserlogin' => $this->faquserlogin(),
            'faquser_group' => $this->faquserGroup(),
            'faquser_right' => $this->faquserRight(),
            'faqvisits' => $this->faqvisits(),
            'faqvoting' => $this->faqvoting(),
            'faqchat_messages' => $this->faqchatMessages(),
            'faqpush_subscriptions' => $this->faqpushSubscriptions(),
        ];
    }

    /**
     * Returns the table names in order.
     *
     * @return string[]
     */
    public function getTableNames(): array
    {
        return array_keys($this->getAllTables());
    }

    public function adminLog(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqadminlog')
            ->integer('id', false)
            ->integer('time', false)
            ->integer('usr', false)
            ->text('text', false)
            ->varchar('hash', 64)
            ->varchar('previous_hash', 64)
            ->varchar('ip', 64, false)
            ->primaryKey('id');
    }

    public function attachment(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqattachment')
            ->integer('id', false)
            ->integer('record_id', false)
            ->varchar('record_lang', 5, false)
            ->char('real_hash', 32, false)
            ->char('virtual_hash', 32, false)
            ->char('password_hash', 40)
            ->varchar('filename', 255, false)
            ->integer('filesize', false)
            ->integer('encrypted', false, 0)
            ->varchar('mime_type', 255)
            ->primaryKey('id');
    }

    public function attachmentFile(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqattachment_file')
            ->char('virtual_hash', 32, false)
            ->blob('contents', false)
            ->primaryKey('virtual_hash');
    }

    public function backup(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqbackup')
            ->integer('id', false)
            ->varchar('filename', 255, false)
            ->varchar('authkey', 255, false)
            ->varchar('authcode', 255, false)
            ->timestamp('created', false)
            ->primaryKey('id');
    }

    public function faqbookmarks(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqbookmarks')
            ->integer('userid')
            ->integer('faqid');
    }

    public function faqcaptcha(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcaptcha')
            ->varchar('id', 6, false)
            ->varchar('useragent', 255, false)
            ->varchar('language', 5, false)
            ->varchar('ip', 64, false)
            ->integer('captcha_time', false)
            ->primaryKey('id');
    }

    public function faqcategories(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcategories')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->integer('parent_id', false)
            ->varchar('name', 255, false)
            ->varchar('description', 255)
            ->integer('user_id', false)
            ->integer('group_id', false, -1)
            ->integer('active', true, 1)
            ->varchar('image', 255)
            ->smallInteger('show_home')
            ->primaryKey(['id', 'lang']);
    }

    public function faqcategoryNews(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcategory_news')
            ->integer('category_id', false)
            ->integer('news_id', false)
            ->primaryKey(['category_id', 'news_id']);
    }

    public function faqcategoryrelations(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcategoryrelations')
            ->integer('category_id', false)
            ->varchar('category_lang', 5, false)
            ->integer('record_id', false)
            ->varchar('record_lang', 5, false)
            ->primaryKey(['category_id', 'category_lang', 'record_id', 'record_lang'])
            ->index('idx_records', ['record_id', 'record_lang']);
    }

    public function faqcategoryGroup(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcategory_group')
            ->integer('category_id', false)
            ->integer('group_id', false)
            ->primaryKey(['category_id', 'group_id']);
    }

    public function faqcategoryUser(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcategory_user')
            ->integer('category_id', false)
            ->integer('user_id', false)
            ->primaryKey(['category_id', 'user_id']);
    }

    public function faqcategoryOrder(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcategory_order')
            ->integer('category_id', false)
            ->integer('parent_id')
            ->integer('position', false)
            ->primaryKey('category_id');
    }

    public function faqchanges(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqchanges')
            ->integer('id', false)
            ->smallInteger('beitrag', false)
            ->varchar('lang', 5, false)
            ->integer('revision_id', false, 0)
            ->integer('usr', false)
            ->integer('datum', false)
            ->text('what')
            ->primaryKey(['id', 'lang']);
    }

    public function faqcomments(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcomments')
            ->integer('id_comment', false)
            ->integer('id', false)
            ->varchar('type', 10, false)
            ->varchar('usr', 255, false)
            ->varchar('email', 255, false)
            ->text('comment', false)
            ->varchar('datum', 64, false)
            ->text('helped')
            ->primaryKey('id_comment');
    }

    public function faqconfig(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqconfig')
            ->varchar('config_name', 255, false, '')
            ->text('config_value')
            ->primaryKey('config_name');
    }

    public function faqdata(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqdata')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->integer('solution_id', false)
            ->integer('revision_id', false, 0)
            ->char('active', 3, false)
            ->integer('sticky', false)
            ->text('keywords')
            ->text('thema', false)
            ->longText('content')
            ->varchar('author', 255, false)
            ->varchar('email', 255, false)
            ->char('comment', 1, true, 'y')
            ->varchar('updated', 15, false)
            ->varchar('date_start', 14, false, '00000000000000')
            ->varchar('date_end', 14, false, '99991231235959')
            ->timestamp('created', true, true)
            ->text('notes')
            ->integer('sticky_order')
            ->fullTextIndex(['keywords', 'thema', 'content'])
            ->primaryKey(['id', 'lang']);
    }

    public function faqdataRevisions(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqdata_revisions')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->integer('solution_id', false)
            ->integer('revision_id', false, 0)
            ->char('active', 3, false)
            ->integer('sticky', false)
            ->text('keywords')
            ->text('thema', false)
            ->longText('content')
            ->varchar('author', 255, false)
            ->varchar('email', 255, false)
            ->char('comment', 1, true, 'y')
            ->varchar('updated', 15, false)
            ->varchar('date_start', 14, false, '00000000000000')
            ->varchar('date_end', 14, false, '99991231235959')
            ->timestamp('created', true, true)
            ->text('notes')
            ->integer('sticky_order')
            ->primaryKey(['id', 'lang', 'solution_id', 'revision_id']);
    }

    public function faqdataGroup(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqdata_group')
            ->integer('record_id', false)
            ->integer('group_id', false)
            ->primaryKey(['record_id', 'group_id']);
    }

    public function faqdataTags(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqdata_tags')
            ->integer('record_id', false)
            ->integer('tagging_id', false)
            ->primaryKey(['record_id', 'tagging_id']);
    }

    public function faqdataUser(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqdata_user')
            ->integer('record_id', false)
            ->integer('user_id', false)
            ->primaryKey(['record_id', 'user_id']);
    }

    public function faqforms(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqforms')
            ->integer('form_id', false)
            ->integer('input_id', false)
            ->varchar('input_type', 1000, false)
            ->varchar('input_label', 500, false)
            ->integer('input_active', false)
            ->integer('input_required', false)
            ->varchar('input_lang', 11, false);
    }

    public function faqglossary(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqglossary')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->varchar('item', 255, false)
            ->text('definition', false)
            ->primaryKey(['id', 'lang']);
    }

    public function faqgroup(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqgroup')
            ->integer('group_id', false)
            ->varchar('name', 25)
            ->text('description')
            ->integer('auto_join')
            ->primaryKey('group_id')
            ->index('idx_name', 'name');
    }

    public function faqgroupRight(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqgroup_right')
            ->integer('group_id', false)
            ->integer('right_id', false)
            ->primaryKey(['group_id', 'right_id']);
    }

    public function faqinstances(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqinstances')
            ->integer('id', false)
            ->varchar('url', 255, false)
            ->varchar('instance', 255, false)
            ->text('comment')
            ->timestamp('created', false)
            ->timestamp('modified', false)
            ->primaryKey('id');
    }

    public function faqinstancesConfig(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqinstances_config')
            ->integer('instance_id', false)
            ->varchar('config_name', 255, false, '')
            ->varchar('config_value', 255)
            ->primaryKey(['instance_id', 'config_name']);
    }

    public function faqapiKeys(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqapi_keys')
            ->integer('id', false)
            ->integer('user_id', false)
            ->varchar('api_key', 64, false)
            ->varchar('name', 255)
            ->text('scopes')
            ->timestamp('last_used_at')
            ->timestamp('expires_at')
            ->timestamp('created', false, true)
            ->primaryKey('id')
            ->uniqueIndex('idx_api_key_unique', 'api_key')
            ->index('idx_api_key_user', 'user_id');
    }

    public function faqoauthClients(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqoauth_clients')
            ->varchar('client_id', 80, false)
            ->varchar('client_secret', 255)
            ->varchar('name', 255, false)
            ->text('redirect_uri')
            ->varchar('grants', 255)
            ->smallInteger('is_confidential', true, 1)
            ->integer('user_id')
            ->timestamp('created', false, true)
            ->primaryKey('client_id');
    }

    public function faqoauthScopes(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqoauth_scopes')
            ->varchar('scope_id', 80, false)
            ->varchar('description', 255)
            ->primaryKey('scope_id');
    }

    public function faqoauthAccessTokens(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqoauth_access_tokens')
            ->varchar('identifier', 100, false)
            ->varchar('client_id', 80, false)
            ->varchar('user_id', 80)
            ->text('scopes')
            ->smallInteger('revoked', true, 0)
            ->timestamp('expires_at', false)
            ->timestamp('created', false, true)
            ->primaryKey('identifier')
            ->index('idx_oauth_access_client', 'client_id')
            ->index('idx_oauth_access_user', 'user_id');
    }

    public function faqoauthRefreshTokens(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqoauth_refresh_tokens')
            ->varchar('identifier', 100, false)
            ->varchar('access_token_identifier', 100, false)
            ->smallInteger('revoked', true, 0)
            ->timestamp('expires_at', false)
            ->timestamp('created', false, true)
            ->primaryKey('identifier')
            ->index('idx_oauth_refresh_access', 'access_token_identifier');
    }

    public function faqoauthAuthCodes(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqoauth_auth_codes')
            ->varchar('identifier', 100, false)
            ->varchar('client_id', 80, false)
            ->varchar('user_id', 80)
            ->text('redirect_uri')
            ->text('scopes')
            ->smallInteger('revoked', true, 0)
            ->timestamp('expires_at', false)
            ->timestamp('created', false, true)
            ->primaryKey('identifier')
            ->index('idx_oauth_code_client', 'client_id')
            ->index('idx_oauth_code_user', 'user_id');
    }

    public function faqnews(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqnews')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->varchar('header', 255, false)
            ->text('artikel', false)
            ->varchar('datum', 14, false)
            ->varchar('author_name', 255)
            ->varchar('author_email', 255)
            ->char('active', 1, true, 'y')
            ->char('comment', 1, true, 'n')
            ->varchar('link', 255)
            ->varchar('linktitel', 255)
            ->varchar('target', 255, false)
            ->primaryKey('id');
    }

    public function faqcustompages(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqcustompages')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->varchar('page_title', 255, false)
            ->varchar('slug', 255, false)
            ->text('content', false)
            ->varchar('author_name', 255, false)
            ->varchar('author_email', 255, false)
            ->char('active', 1, false, 'n')
            ->timestamp('created', false, true)
            ->timestamp('updated')
            ->varchar('seo_title', 60)
            ->varchar('seo_description', 160)
            ->varchar('seo_robots', 50, false, 'index,follow')
            ->primaryKey(['id', 'lang'])
            ->index('idx_custompages_slug', ['slug', 'lang']);
    }

    public function faqquestions(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqquestions')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->varchar('username', 100, false)
            ->varchar('email', 100, false)
            ->integer('category_id', false)
            ->text('question', false)
            ->varchar('created', 20, false)
            ->char('is_visible', 1, true, 'Y')
            ->integer('answer_id', false, 0)
            ->primaryKey('id');
    }

    public function faqright(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqright')
            ->integer('right_id', false)
            ->varchar('name', 50)
            ->text('description')
            ->integer('for_users', true, 1)
            ->integer('for_groups', true, 1)
            ->integer('for_sections', true, 1)
            ->primaryKey('right_id');
    }

    public function faqsearches(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqsearches')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->varchar('searchterm', 255, false)
            ->timestamp('searchdate', false, true)
            ->primaryKey(['id', 'lang'])
            ->index('idx_faqsearches_searchterm', 'searchterm')
            ->index('idx_faqsearches_date_term', ['searchdate', 'searchterm'])
            ->index('idx_faqsearches_date_term_lang', ['searchdate', 'searchterm', 'lang']);
    }

    public function faqseo(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqseo')
            ->integer('id', false)
            ->varchar('type', 32, false)
            ->integer('reference_id', false)
            ->varchar('reference_language', 5, false)
            ->text('title')
            ->text('description')
            ->text('slug')
            ->timestamp('created', false, true)
            ->primaryKey('id');
    }

    public function faqsessions(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqsessions')
            ->integer('sid', false)
            ->integer('user_id', false)
            ->varchar('ip', 64, false)
            ->integer('time', false)
            ->primaryKey('sid')
            ->index('idx_time', 'time');
    }

    public function faqstopwords(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqstopwords')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->varchar('stopword', 64, false)
            ->primaryKey(['id', 'lang']);
    }

    public function faqtags(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqtags')
            ->integer('tagging_id', false)
            ->varchar('tagging_name', 255, false)
            ->primaryKey(['tagging_id', 'tagging_name']);
    }

    public function faquser(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faquser')
            ->integer('user_id', false)
            ->varchar('login', 128, false)
            ->varchar('session_id', 150)
            ->integer('session_timestamp')
            ->varchar('ip', 64)
            ->varchar('account_status', 50)
            ->varchar('last_login', 14)
            ->varchar('auth_source', 100)
            ->varchar('member_since', 14)
            ->varchar('remember_me', 150)
            ->smallInteger('success', true, 1)
            ->smallInteger('is_superadmin', true, 0)
            ->smallInteger('login_attempts', true, 0)
            ->text('refresh_token')
            ->text('access_token')
            ->varchar('code_verifier', 255)
            ->text('jwt')
            ->text('webauthnkeys')
            ->primaryKey('user_id');
    }

    public function faquserdata(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faquserdata')
            ->integer('user_id', false)
            ->varchar('last_modified', 14)
            ->varchar('display_name', 128)
            ->varchar('email', 128)
            ->smallInteger('is_visible', true, 0)
            ->smallInteger('twofactor_enabled', true, 0)
            ->varchar('secret', 128);
    }

    public function faquserlogin(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faquserlogin')
            ->varchar('login', 128, false)
            ->varchar('pass', 80)
            ->varchar('domain', 255)
            ->primaryKey('login');
    }

    public function faquserGroup(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faquser_group')
            ->integer('user_id', false)
            ->integer('group_id', false)
            ->primaryKey(['user_id', 'group_id']);
    }

    public function faquserRight(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faquser_right')
            ->integer('user_id', false)
            ->integer('right_id', false)
            ->primaryKey(['user_id', 'right_id']);
    }

    public function faqvisits(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqvisits')
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->integer('visits', false)
            ->integer('last_visit', false)
            ->primaryKey(['id', 'lang']);
    }

    public function faqvoting(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqvoting')
            ->integer('id', false)
            ->integer('artikel', false)
            ->integer('vote', false)
            ->integer('usr', false)
            ->varchar('datum', 20, true, '')
            ->varchar('ip', 15, true, '')
            ->primaryKey('id');
    }

    public function faqchatMessages(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqchat_messages')
            ->autoIncrement('id')
            ->integer('sender_id', false)
            ->integer('recipient_id', false)
            ->text('message', false)
            ->boolean('is_read', false, false)
            ->timestamp('created_at', false, true)
            ->index('idx_chat_sender', 'sender_id')
            ->index('idx_chat_recipient', 'recipient_id')
            ->index('idx_chat_conversation', ['sender_id', 'recipient_id'])
            ->index('idx_chat_created', 'created_at');
    }

    public function faqpushSubscriptions(): TableBuilder
    {
        return new TableBuilder($this->dialect)
            ->table('faqpush_subscriptions')
            ->autoIncrement('id')
            ->integer('user_id', false)
            ->text('endpoint', false)
            ->varchar('endpoint_hash', 64, false)
            ->text('public_key', false)
            ->text('auth_token', false)
            ->varchar('content_encoding', 50)
            ->timestamp('created_at', false, true)
            ->index('idx_push_user_id', 'user_id')
            ->uniqueIndex('idx_push_endpoint_hash', 'endpoint_hash');
    }
}
