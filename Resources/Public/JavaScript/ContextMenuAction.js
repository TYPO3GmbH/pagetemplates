/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Pagetemplates/ContextMenuActions
 *
 * JavaScript to handle "dereference" click in context menu of reference content elements in page module.
 * @exports T3G/Pagetemplates/ContextMenuActions
 */
define(function () {
    'use strict';

    /**
     * @exports T3G/Pagetemplates/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.getReturnUrl = function () {
        return top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
    };

    /**
     * Dereference an item from reference element
     *
     * @param {string} table
     * @param {int} uid of the element
     */
    ContextMenuActions.createPageFromTemplate = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl(
            top.TYPO3.settings.CreatePageFromTemplate.moduleUrl + '&targetUid=' + top.rawurlencode(uid) + '&returnUrl=' +  ContextMenuActions.getReturnUrl()
        );

    };

    return ContextMenuActions;
});