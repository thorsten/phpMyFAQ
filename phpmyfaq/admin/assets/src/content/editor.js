/**
 * TinyMCE for phpMyFAQ
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-31
 */

import { Jodit } from 'jodit';
import 'jodit/esm/plugins/class-span/class-span.js';
import 'jodit/esm/plugins/clean-html/clean-html.js';
import 'jodit/esm/plugins/clipboard/clipboard.js';
import 'jodit/esm/plugins/copy-format/copy-format.js';
import 'jodit/esm/plugins/delete/delete.js';
import 'jodit/esm/plugins/fullsize/fullsize.js';
import 'jodit/esm/plugins/hr/hr.js';
import 'jodit/esm/plugins/image/image.js';
import 'jodit/esm/plugins/image-processor/image-processor.js';
import 'jodit/esm/plugins/image-properties/image-properties.js';
import 'jodit/esm/plugins/indent/indent.js';
import 'jodit/esm/plugins/justify/justify.js';
import 'jodit/esm/plugins/line-height/line-height.js';
import 'jodit/esm/plugins/media/media.js';
import 'jodit/esm/plugins/preview/preview.js';
import 'jodit/esm/plugins/print/print.js';
import 'jodit/esm/plugins/resizer/resizer.js';
import 'jodit/esm/plugins/search/search.js';
import 'jodit/esm/plugins/select/select.js';
import 'jodit/esm/plugins/source/source.js';
import 'jodit/esm/plugins/symbols/symbols.js';
import 'jodit/esm/modules/uploader/uploader.js';
import 'jodit/esm/plugins/video/video.js';

export const renderEditor = () => {
  const editor = document.getElementById('editor');
  if (!editor) {
    return;
  }

  const joditEditor = Jodit.make(editor, {
    zIndex: 0,
    readonly: false,
    activeButtonsInReadOnly: ['source', 'fullsize', 'print', 'about', 'dots'],
    toolbarButtonSize: 'middle',
    theme: 'default',
    saveModeInCookie: false,
    spellcheck: true,
    editorCssClass: false,
    triggerChangeEvent: true,
    width: 'auto',
    height: 'auto',
    minHeight: 100,
    direction: '',
    language: 'auto',
    debugLanguage: false,
    i18n: 'en',
    tabIndex: -1,
    toolbar: true,
    enter: 'P',
    defaultMode: Jodit.MODE_WYSIWYG,
    useSplitMode: false,
    colors: {
      greyscale: [
        '#000000',
        '#434343',
        '#666666',
        '#999999',
        '#B7B7B7',
        '#CCCCCC',
        '#D9D9D9',
        '#EFEFEF',
        '#F3F3F3',
        '#FFFFFF',
      ],
      palette: [
        '#980000',
        '#FF0000',
        '#FF9900',
        '#FFFF00',
        '#00F0F0',
        '#00FFFF',
        '#4A86E8',
        '#0000FF',
        '#9900FF',
        '#FF00FF',
      ],
      full: [
        '#E6B8AF',
        '#F4CCCC',
        '#FCE5CD',
        '#FFF2CC',
        '#D9EAD3',
        '#D0E0E3',
        '#C9DAF8',
        '#CFE2F3',
        '#D9D2E9',
        '#EAD1DC',
        '#DD7E6B',
        '#EA9999',
        '#F9CB9C',
        '#FFE599',
        '#B6D7A8',
        '#A2C4C9',
        '#A4C2F4',
        '#9FC5E8',
        '#B4A7D6',
        '#D5A6BD',
        '#CC4125',
        '#E06666',
        '#F6B26B',
        '#FFD966',
        '#93C47D',
        '#76A5AF',
        '#6D9EEB',
        '#6FA8DC',
        '#8E7CC3',
        '#C27BA0',
        '#A61C00',
        '#CC0000',
        '#E69138',
        '#F1C232',
        '#6AA84F',
        '#45818E',
        '#3C78D8',
        '#3D85C6',
        '#674EA7',
        '#A64D79',
        '#85200C',
        '#990000',
        '#B45F06',
        '#BF9000',
        '#38761D',
        '#134F5C',
        '#1155CC',
        '#0B5394',
        '#351C75',
        '#733554',
        '#5B0F00',
        '#660000',
        '#783F04',
        '#7F6000',
        '#274E13',
        '#0C343D',
        '#1C4587',
        '#073763',
        '#20124D',
        '#4C1130',
      ],
    },
    colorPickerDefaultTab: 'background',
    imageDefaultWidth: 300,
    removeButtons: [],
    disablePlugins: [],
    extraButtons: [],
    buttons: [
      'source',
      '|',
      'bold',
      'strikethrough',
      'underline',
      'italic',
      '|',
      'ul',
      'ol',
      'superscript',
      'subscript',
      '|',
      'justify',
      'outdent',
      'indent',
      '|',
      'font',
      'fontsize',
      'lineHeight',
      'brush',
      'paragraph',
      '|',
      'copy',
      'cut',
      'paste',
      'selectall',
      '|',
      'file',
      'image',
      'video',
      'table',
      'link',
      '|',
      'undo',
      'redo',
      '|',
      'classSpan',
      'hr',
      'eraser',
      'copyformat',
      '|',
      'symbols',
      'fullsize',
      'preview',
      'print',
    ],
    events: {},
    textIcons: false,
    uploader: {
      url: '/admin/api/content/images?csrf=' + document.getElementById('pmf-csrf-token').value,
      format: 'json',
      isSuccess: (response) => {
        return !response.error;
      },
      getMessage: (response) => {
        return response.msg;
      },
    },
    filebrowser: {
      ajax: {
        url: '/admin/api/media-browser',
        contentType: 'application/json; charset=UTF-8',
      },
      createNewFolder: false,
      deleteFolder: false,
      moveFolder: false,
      showFoldersPanel: false,
      showFileSize: true,
      showFileName: true,
    },
  });
};
