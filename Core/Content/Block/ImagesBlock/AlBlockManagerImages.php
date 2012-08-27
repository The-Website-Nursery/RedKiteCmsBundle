<?php

namespace AlphaLemon\AlphaLemonCmsBundle\Core\Content\Block\ImagesBlock;

use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Block\AlBlockManagerContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Validator\AlParametersValidatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Block\JsonBlock\AlBlockManagerJsonBlock;
use AlphaLemon\ThemeEngineBundle\Core\Asset\AlAsset;

/**
 * AlBlockManagerImages manages a content made by a serie of images
 *
 * @author alphalemon <webmaster@alphalemon.com>
 */
abstract class AlBlockManagerImages extends AlBlockManagerContainer
{
    protected function edit(array $values)
    {
        $images = AlBlockManagerJsonBlock::decodeJsonContent($this->alBlock->getHtmlContent());
        $savedImages = array_map(function($el){ return $el['image']; }, $images);

        if(array_key_exists('AddFile', $values)) {
            $file = $values["AddFile"];

            $asset = new AlAsset($this->container->get('kernel'), '@AlphaLemonCmsBundle');
            $absolutePath = $asset->getAbsolutePath() . '/' . $this->container->getParameter('alphalemon_cms.upload_assets_dir');

            $imageFile = "/" . $absolutePath . "/" . preg_replace('/http?:\/\/[^\/]+/', '', $file);
            if (in_array($imageFile, $savedImages))
            {
                throw new \Exception("The image file has already been added");
            }

            $images[]['image'] = $imageFile;
        }

        if(array_key_exists('RemoveFile', $values)) {
            $fileToRemove = $values["RemoveFile"];
            $file = $this->container->getParameter('kernel.root_dir') . '/../' . $this->container->getParameter('alphalemon_cms.web_folder') . $fileToRemove;

            $key = array_search($fileToRemove, $savedImages);
            if (false !== $key)  unset($images[$key]);
        }

        $values["HtmlContent"] = json_encode($images);

        return parent::edit($values);
    }
}