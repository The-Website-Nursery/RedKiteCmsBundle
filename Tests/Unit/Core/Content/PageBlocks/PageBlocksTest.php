<?php
/**
 * This file is part of the RedKite CMS Application and it is distributed
 * under the MIT License. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    MIT License
 *
 */

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\Unit\Core\Content\PageBlocks;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\TestCase;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\PageBlocks\PageBlocks;

/**
 * PageBlocksTest
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
class PageBlocksTest extends TestCase
{
    private $blockRepository;
    private $pageBlocks;

    protected function setUp()
    {
        parent::setUp();

        $this->blockRepository = $this->getMockBuilder('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Propel\BlockRepositoryPropel')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->factoryRepository = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Factory\FactoryRepositoryInterface');
        $this->factoryRepository->expects($this->any())
            ->method('createRepository')
            ->will($this->returnValue($this->blockRepository));

        $this->pageBlocks = new PageBlocks($this->factoryRepository);
    }
    
    
    /**
     * @dataProvider invalidArgumentsProvider
     * @expectedException \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Exception\Content\General\InvalidArgumentTypeException
     */
    public function testAnExceptionIsThrownWhenArgumentsAreNotIntegers($idLanguage, $idPage)
    {
        $this->blockRepository->expects($this->never())
            ->method('retrieveContents')
        ;

        $this->pageBlocks->refresh($idLanguage, $idPage);

        $this->pageBlocks->getBlocks();
    }
    
    public function invalidArgumentsProvider()
    {
        return array(
            array(
                "foo",
                2,
            ),
            array(
                2,
                "foo",
            ),
        );
    }
    
    public function testSetBlocks()
    {
        $blocks = array(
            $this->setUpBlock('logo'),
            $this->setUpBlock('menu'),
            $this->setUpBlock('logo'),
        );

        $this->assertCount(0, $this->pageBlocks->getBlocks());
        $this->pageBlocks->setBlocks($blocks);
        $this->assertCount(2, $this->pageBlocks->getBlocks());
    }
    
    public function testAnEmptyArrayIsRetrievedWhenAnyBlockExists()
    {
        $this->blockRepository->expects($this->once())
            ->method('retrieveContents')
            ->will($this->returnValue(array()));

        $this->pageBlocks->refresh(2, 2);

        $this->assertCount(0, $this->pageBlocks->getBlocks());
    }

    public function testContentsAreRetrieved()
    {
        $blocks = array(
            $this->setUpBlock('logo', 'Text'),
            $this->setUpBlock('logo', 'Text'),
            $this->setUpBlock('menu', 'Menu'),
        );

        $this->blockRepository->expects($this->once())
            ->method('retrieveContents')
            ->will($this->returnValue($blocks));

        $this->pageBlocks->refresh(2, 2);
        $this->pageBlocks->refresh(2, 2);

        $this->assertEquals(2, count($this->pageBlocks->getBlocks()));
        $this->assertEquals(2, count($this->pageBlocks->getSlotBlocks('logo')));
        $this->assertEquals(1, count($this->pageBlocks->getSlotBlocks('menu')));
        $this->assertEquals(array('Text', 'Menu'), $this->pageBlocks->getBlockTypes());
        $this->assertEquals(array('idLanguage' => 2, 'idPage' => 2,), $this->pageBlocks->getPageInformation());
    }
    
    public function testBlockIsAdded()
    {
        $this->assertEquals($this->pageBlocks, $this->pageBlocks->add("logo", array('Content' => 'My value')));

        $this->assertCount(1, $this->pageBlocks->getBlocks());
        $this->checkOneBlock('logo', 'My value');
    }

    public function testBlockIsEdited()
    {
        $this->pageBlocks->add("logo", array('Content' => 'My value'));
        $this->pageBlocks->add("logo", array('Content' => 'My new value'), 0);

        $this->assertCount(1, $this->pageBlocks->getBlocks());
        $this->checkOneBlock('logo', 'My new value');
    }

    public function testBlockIsAddedWhenAnInvalidPositionNumberIsGiven()
    {
        $this->pageBlocks->add("logo", array('Content' => 'My value'));
        $this->pageBlocks->add("logo", array('Content' => 'My new value'), 5);

        $this->assertCount(1, $this->pageBlocks->getBlocks());
        $block = $this->pageBlocks->getSlotBlocks('logo');
        $this->assertCount(2, $block);
    }
    
    public function testNullContents()
    {
        $this->pageBlocks->addRange(array("logo" => null));

        $this->assertCount(1, $this->pageBlocks->getBlocks());
        $block = $this->pageBlocks->getSlotBlocks('logo');
        $this->assertNull($block);
    }

    public function testARangeOfBlocksIsAdded()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value'), array('Content' => 'My new value'))));

        $this->assertCount(1, $this->pageBlocks->getBlocks());
        $block = $this->pageBlocks->getSlotBlocks('logo');
        $this->assertCount(2, $block);
        $this->assertEquals('My value', $block[0]['Content']);
        $this->assertEquals('My new value', $block[1]['Content']);
    }
    
    public function testARangeOfBlocksIsOverriden()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value'), array('Content' => 'My new value'))));
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'Overrided value'))), true);

        $this->assertCount(1, $this->pageBlocks->getBlocks());
        $block = $this->pageBlocks->getSlotBlocks('logo');
        $this->assertCount(1, $block);
        $this->assertEquals('Overrided value', $block[0]['Content']);
    }

    public function testARangeOfBlocksIsAddedOnMoreSlots()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value'), array('Content' => 'My new value')),
            "nav_menu" => array(array('Content' => 'My value'))));

        $this->assertCount(2, $this->pageBlocks->getBlocks());
    }

    /**
     * @expectedException RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Exception\General\InvalidArgumentException
     */
    public function testAnExeptionIsThrowsWhenTryingToClearANonExistentSlot()
    {
        $this->assertEquals($this->pageBlocks, $this->pageBlocks->clearSlotBlocks('logo'));
    }

    public function testASlotIsCleared()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value'))));
        $this->assertCount(1, $this->pageBlocks->getSlotBlocks('logo'));

        $this->assertEquals($this->pageBlocks, $this->pageBlocks->clearSlotBlocks('logo'));
        $this->assertCount(0, $this->pageBlocks->getSlotBlocks('logo'));
    }

    public function testAllSlotsAreCleared()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value')), "nav-menu" => array(array('Content' => 'My value'))));
        $this->assertCount(2, $this->pageBlocks->getBlocks());
        $this->assertCount(1, $this->pageBlocks->getSlotBlocks('logo'));
        $this->assertCount(1, $this->pageBlocks->getSlotBlocks('nav-menu'));

        $this->assertEquals($this->pageBlocks, $this->pageBlocks->clearSlots());
        $this->assertCount(2, $this->pageBlocks->getBlocks());
        $this->assertCount(0, $this->pageBlocks->getSlotBlocks('logo'));
        $this->assertCount(0, $this->pageBlocks->getSlotBlocks('nav-menu'));
    }

    /**
     * @expectedException RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Exception\General\InvalidArgumentException
     */
    public function testAnExeptionIsThrowsWhenTryingToRemoveANonExistentSlot()
    {
        $this->assertEquals($this->pageBlocks, $this->pageBlocks->removeSlot('logo'));
    }

    public function testASlotIsRemoved()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value'))));
        $this->assertCount(1, $this->pageBlocks->getBlocks());

        $this->assertEquals($this->pageBlocks, $this->pageBlocks->removeSlot('logo'));
        $this->assertCount(0, $this->pageBlocks->getBlocks());
    }

    public function testAllSlotsAreRemoved()
    {
        $this->pageBlocks->addRange(array("logo" => array(array('Content' => 'My value')), "nav-menu" => array(array('Content' => 'My value'))));
        $this->assertCount(2, $this->pageBlocks->getBlocks());

        $this->assertEquals($this->pageBlocks, $this->pageBlocks->removeSlots());
        $this->assertCount(0, $this->pageBlocks->getBlocks());
    }

    private function checkOneBlock($slotName, $expectedContent)
    {
        $block = $this->pageBlocks->getSlotBlocks($slotName);
        $this->assertTrue(count($block) == 1);
        $this->assertEquals($expectedContent, $block[0]['Content']);
    }

    private function setUpBlock($slotName, $type = null)
    {
        $block = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\Block');
        $block->expects($this->once())
            ->method('getSlotName')
            ->will($this->returnValue($slotName));

        if (null !== $type) {
            $block->expects($this->once())
                ->method('getType')
                ->will($this->returnValue($type));
        }

        return $block;
    }
}
