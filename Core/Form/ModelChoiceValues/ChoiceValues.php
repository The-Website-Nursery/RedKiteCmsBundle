<?php
/*
 * This file is part of the AlphaLemon CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 * 
 * @license    GPL LICENSE Version 2.0
 * 
 */

namespace AlphaLemon\AlphaLemonCmsBundle\Core\Form\ModelChoiceValues;

use Symfony\Component\Finder\Finder;
use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\AlThemeQuery;
use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\AlPageQuery;

use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\AlLanguageQuery;
use AlphaLemon\ThemeEngineBundle\Core\Autoloader\Base\BundlesAutoloaderComposer;
use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Repository\PageRepositoryInterface;
use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Repository\LanguageRepositoryInterface;
use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Repository\ThemeRepositoryInterface;

/**
 * Retrieves form the database the values used in the forms
 *
 * @author alphalemon <webmaster@alphalemon.com>
 */
class ChoiceValues
{
    public static function getPages(PageRepositoryInterface $pageModel, $withNoneOption = true)
    {
        $result = array();
        if($withNoneOption) $pages["none"] = " ";
        $pages = $pageModel->activePages();
        foreach($pages as $page)
        {
            $result[$page->getId()] = $page->getPageName();
        }

        return $result;
    }

    public static function getLanguages(LanguageRepositoryInterface $languageModel, $withNoneOption = true)
    {
        $result = array();
        if($withNoneOption) $languages["none"] = " ";
        $languages = $languageModel->activeLanguages();
        foreach($languages as $language)
        {
            $result[$language->getId()] = $language->getLanguage();
        }

        return $result;
    }

    public static function getTemplates(ThemeRepositoryInterface $themeModel)
    {
        // Default templates
        $templates = array("none" => " ");
        
        // Find the current active theme
        $theme = $themeModel->activeBackend(); 
        if(null === $theme) return $templates;
        
        $composer = new BundlesAutoloaderComposer('AlphaLemon\\Theme' );
        $bundles = $composer->getBundles();  
        
        // Retrieves the path for the current theme
        $themeNamespace = 'AlphaLemon\\Theme\\' . $theme->getThemeName();
        if(!array_key_exists($themeNamespace, $bundles)) return $templates;        
        $themeDir = $bundles[$themeNamespace];
        
        // Points the templates' folder and retrieve the templates
        $templatesPath = sprintf('%s/Resources/views/Theme', $themeDir);
        $finder = new Finder();
        $templateFiles = $finder->files()->name('*.twig')->depth(0)->sortByName()->in($templatesPath);

        foreach($templateFiles as $templateFile)
        {
            $info = pathinfo($templateFile);
            $templateName = basename($info['filename'], '.html');
            $templates[$templateName] = $templateName;
        }

        return $templates;
    }
}