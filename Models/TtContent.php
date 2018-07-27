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
 * Model representing a single record of the tt_content database table.
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
 * @property int    t3ver_move_id
 * @property int    t3_origuid
 * @property int    tstamp
 * @property int    crdate
 * @property int    cruser_id
 * @property int    hidden
 * @property int    sorting
 * @property string CType
 * @property string header
 * @property string header_position
 * @property string bodytext
 * @property int    image
 * @property int    imagewidth
 * @property int    imageorient
 * @property string imagecaption
 * @property int    imagecols
 * @property int    imageborder
 * @property int    media
 * @property int    layout
 * @property int    deleted
 * @property int    cols
 * @property string records
 * @property string pages
 * @property int    starttime
 * @property int    endtime
 * @property int    colPos
 * @property string subheader
 * @property int    spaceBefore
 * @property int    spaceAfter
 * @property string fe_group
 * @property string header_link
 * @property string imagecaption_position
 * @property string image_link
 * @property int    image_zoom
 * @property int    image_noRows
 * @property int    image_effects
 * @property int    image_compression
 * @property string altText
 * @property string titleText
 * @property string longdescURL
 * @property string header_layout
 * @property string menu_type
 * @property string list_type
 * @property int    table_border
 * @property int    table_cellspacing
 * @property int    table_cellpadding
 * @property int    table_bgColor
 * @property string select_key
 * @property int    sectionIndex
 * @property int    linkToTop
 * @property int    filelink_size
 * @property int    section_frame
 * @property int    date
 * @property string multimedia
 * @property int    image_frames
 * @property int    recursive
 * @property int    imageheight
 * @property int    rte_enabled
 * @property int    sys_language_uid
 * @property int    tx_impexp_origuid
 * @property string pi_flexform
 * @property int    l18n_parent
 * @property mixed  l18n_diffsource
 * @property int    module_sys_dmail_category
 * @property int    tx_links
 * @property string target
 * @property string accessibility_title
 * @property int    accessibility_bypass
 * @property string accessibility_bypass_text
 * @property string image1
 * @property string images
 * @property string files
 * @property string link
 * @property string label
 * @property string config
 * @property string file_collections
 * @property string filelink_sorting
 * @property string selected_categories
 * @property string category_field
 * @property int    categories
 * @property string align
 * @property string video
 * @property int    editlock
 * @property string rowDescription
 * @property int    bullets_type
 * @property int    uploads_description
 * @property int    uploads_type
 * @property string frame_class
 * @property string space_before_class
 * @property string space_after_class
 * @property string table_class
 * @property int    table_delimiter
 * @property int    table_enclosure
 * @property int    storage_pid
 * @property string l10n_state
 * @property int    l10n_source
 */
class TtContent extends AbstractModel
{
    /**
     * Database table name this model is associated with.
     *
     * @var string
     */
    protected $table = 'tt_content';

    /**
     * Returns the page model this content element is used on.
     *
     * @return Page
     */
    public function getPage(): Page
    {
        return Page::find($this->getParentKey());
    }

    /**
     * Injects the specified array into a new model.
     *
     * @param array $data
     *
     * @return TtContent
     */
    public static function inject(array $data): self
    {
        return self::mapArrayToModel($data);
    }
}
