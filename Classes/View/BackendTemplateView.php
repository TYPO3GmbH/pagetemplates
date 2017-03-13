<?php
declare(strict_types=1);

namespace T3G\AgencyPack\Pagetemplates\View;

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

use TYPO3\CMS\Backend\View\BackendTemplateView as CoreBackendTemplateView;

/**
 * Extend the core backend template view with the ability to overwrite templates
 */
class BackendTemplateView extends CoreBackendTemplateView
{

    /**
     * Set the root path(s) to the templates.
     * If set, overrides the one determined from $this->templateRootPathPattern
     *
     * @param array $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->templateRootPathPattern
     * @return void
     * @api
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->templateView->setTemplateRootPaths($templateRootPaths);
    }

    /**
     * Set the root path(s) to the partials.
     * If set, overrides the one determined from $this->partialRootPathPattern
     *
     * @param array $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->partialRootPathPattern
     * @return void
     * @api
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->templateView->setPartialRootPaths($partialRootPaths);
    }

    /**
     * Set the root path(s) to the layouts.
     * If set, overrides the one determined from $this->layoutRootPathPattern
     *
     * @param array $layoutRootPaths Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
     * @return void
     * @api
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->templateView->setLayoutRootPaths($layoutRootPaths);
    }
}
