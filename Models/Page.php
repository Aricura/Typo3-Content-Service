<?php

declare(strict_types=1);

/*
 * The Typo3-Content-Service is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The Typo3-Content-Service is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the Typo3-Content-Service. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Typo3ContentService\Models;

/**
 * Model representing a single record of the pages database table.
 *
 * @property int    uid
 * @property int    pid
 * @property int    t3ver_oid
 * @property int    t3ver_id
 * @property int    t3ver_wsid
 * @property string t3ver_label
 * @property int    t3ver_state
 * @property int    t3ver_stage
 * @property int    t3ver_count
 * @property int    t3ver_tstamp
 * @property int    t3ver_swapmode
 * @property int    t3ver_move_id
 * @property int    t3_origuid
 * @property int    tstamp
 * @property int    sorting
 * @property int    deleted
 * @property int    perms_userid
 * @property int    perms_groupid
 * @property int    perms_user
 * @property int    perms_group
 * @property int    perms_everybody
 * @property int    editlock
 * @property int    crdate
 * @property int    cruser_id
 * @property int    hidden
 * @property string title
 * @property int    doktype
 * @property string TSconfig
 * @property int    storage_pid
 * @property int    is_siteroot
 * @property int    php_tree_stop
 * @property int    tx_impexp_origuid
 * @property string url
 * @property int    starttime
 * @property int    endtime
 * @property int    urltype
 * @property int    shortcut
 * @property int    shortcut_mode
 * @property int    no_cache
 * @property string fe_group
 * @property string subtitle
 * @property int    layout
 * @property string target
 * @property int    media
 * @property int    lastUpdated
 * @property string keywords
 * @property int    cache_timeout
 * @property int    newUntil
 * @property string description
 * @property int    no_search
 * @property int    SYS_LASTCHANGED
 * @property string abstract
 * @property string module
 * @property int    extendToSubpages
 * @property string author
 * @property string author_email
 * @property string nav_title
 * @property int    nav_hide
 * @property int    content_from_pid
 * @property int    mount_pid
 * @property int    mount_pid_ol
 * @property string alias
 * @property int    l18n_cfg
 * @property int    fe_login_mode
 * @property string backend_layout
 * @property string backend_layout_next_level
 * @property string ts_config_includes
 * @property int    categories
 */
class Page extends AbstractModel
{
    /**
     * Database table name this model is associated with.
     *
     * @var string
     */
    protected $table = 'pages';

    /**
     * Column name where the language index of the model is stored in.
     * This information may be empty if the model has no language index / isn't translatable.
     * Default set to 'sys_language_uid'.
     *
     * @var string
     */
    protected $languageIndexColumnName = '';
}
