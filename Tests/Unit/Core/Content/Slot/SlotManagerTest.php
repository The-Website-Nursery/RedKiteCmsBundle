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

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\Unit\Core\Content\Slot;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\Unit\Core\Content\Base\ContentManagerBase;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Slot\SlotManager;
use RedKiteLabs\ThemeEngineBundle\Core\ThemeSlots\Slot;

/**
 * SlotManagerTest
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
class SlotManagerTest extends ContentManagerBase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Block\BlockManagerFactoryInterface');

        $this->blockRepository = 
            $this->getMockBuilder('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Propel\BlockRepositoryPropel')
                 ->disableOriginalConstructor()
                 ->getMock()
        ;
        
        $this->blocksAdded = 
            $this->getMockBuilder('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Slot\Blocks\BlocksAdder')
                 ->disableOriginalConstructor()
                 ->getMock()
        ;
        
        $this->blocksRemover = 
            $this->getMockBuilder('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Slot\Blocks\BlocksRemover')
                 ->disableOriginalConstructor()
                 ->getMock()
        ;
        
        $this->blockManagersCollection = 
            $this->getMockBuilder('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Slot\Blocks\BlockManagersCollection')
                 ->getMock()
        ;
        

        $this->slot = new Slot('test', array('repeated' => 'page'));
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory);
    }
    
    public function testSlotInjectedBySetters()
    {
        $slot = 
            $this->getMockBuilder('RedKiteLabs\ThemeEngineBundle\Core\ThemeSlots\Slot')
                 ->disableOriginalConstructor()
                 ->getMock()
        ;        
        
        $slot->expects($this->once())
             ->method('getSlotName')
             ->will($this->returnValue('logo'))
        ;
        
        $this->assertEquals($this->slotManager, $this->slotManager->setSlot($slot));
        $this->assertEquals($slot, $this->slotManager->getSlot());
        $this->assertEquals('logo', $this->slotManager->getSlotName());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage exception_boolean_value_required_for_setForceSlotAttributes
     */
    public function testSetForceSlotAttributesWantsAbooleanAsArgument()
    {
        $this->slotManager->setForceSlotAttributes('fake');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage exception_boolean_value_required_for_setSkipSiteLevelBlocks
     */
    public function testSetSkipSiteLevelBlocksWantsAbooleanAsArgument()
    {
        $this->slotManager->setSkipSiteLevelBlocks('fake');
    }
    
    public function testGetRepeated()
    {
        $this->assertEquals('page', $this->slotManager->getRepeated());
    }
    
    public function testGetForceSlotAttributes()
    {
        $this->assertFalse($this->slotManager->getForceSlotAttributes());
        $this->slotManager->setForceSlotAttributes(true);
        $this->assertTrue($this->slotManager->getForceSlotAttributes());
    }

    /**
     * @dataProvider invalidOptionsProvider
     * @expectedException \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Exception\Content\General\InvalidArgumentTypeException
     */
    public function testAddBlockFailsWhenReceivesAnInvalidLanguageId($options)
    {
        $this
            ->eventsHandler
            ->expects($this->never())
            ->method('createEvent')
        ;

        $factory = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Block\BlockManagerFactoryInterface');
        $factory
            ->expects($this->any())
            ->method('createBlockManager')
            ->will($this->throwException(new \InvalidArgumentException));

        $options = array(
            "idLanguage" => "foo",
            "idPage" => 2,
        );
        
        $this->slotManager->addBlock($options);
    }
    
    public function invalidOptionsProvider()
    {
        return array(
            array(
                "idLanguage" => "foo",
                "idPage" => 2,
            ),
            array(
                "idLanguage" => 2,
                "idPage" => "foo",
            ),
            array(
                "idLanguage" => 2,
                "idPage" => 2,
                "type" => 'foo',
            ),
        );
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddBlockFails()
    {
        $this->blocksAdded
            ->expects($this->once())
            ->method('add')
            ->will($this->throwException(new \InvalidArgumentException))
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, $this->blocksAdded);
        $this->slotManager
            ->addBlock(array(
                "idLanguage" => 2,
                "idPage" => 2,
                "type" => 'Text',
            )
        );
    }
    
    /**
     * @dataProvider addProvider
     */
    public function testAddNewBlock($options, $skipSiteLevelBlocks, $forceSlotAttributes, $expectedOptions)
    {
        $this->blocksAdded
            ->expects($this->once())
            ->method('add')
            ->with($this->slot, $this->blockManagersCollection, $expectedOptions)
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, $this->blocksAdded, null, $this->blockManagersCollection);
        $this->slotManager
            ->setSkipSiteLevelBlocks($skipSiteLevelBlocks)
            ->setForceSlotAttributes($forceSlotAttributes)
            ->addBlock($options);
    }
    
    public function addProvider()
    {
        return array(
            array(
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                ),
                false,
                false,
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => null,
                    "insertDirection" => "bottom",
                    "skipSiteLevelBlocks" => 0,
                    "forceSlotAttributes" => 0,
                ),
                true,
            ),
            array(
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                ),
                false,
                false,
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "bottom",
                    "skipSiteLevelBlocks" => 0,
                    "forceSlotAttributes" => 0,
                ),
                true,
            ),
            array(
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "top",
                ),
                false,
                false,
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "top",
                    "skipSiteLevelBlocks" => 0,
                    "forceSlotAttributes" => 0,
                ),
                true,
            ),
            array(
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "top",
                ),
                true,
                true,
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "top",
                    "skipSiteLevelBlocks" => 1,
                    "forceSlotAttributes" => 1,
                ),
                true,
            ),
            array(
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "top",
                ),
                true,
                true,
                array(
                    "idLanguage" => 2,
                    "idPage" => 2,
                    "type" => 'Text',
                    "referenceBlockId" => 2,
                    "insertDirection" => "top",
                    "skipSiteLevelBlocks" => 1,
                    "forceSlotAttributes" => 1,
                ),
                false,
            ),
        );
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEditBlockFails()
    {
        $this->blocksAdded
            ->expects($this->once())
            ->method('edit')
            ->will($this->throwException(new \InvalidArgumentException))
        ;
        
        $blockManager = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Block\BlockManagerInterface');
        $this->blockManagersCollection
            ->expects($this->once())
            ->method('getBlockManager')
            ->with(2)
            ->will($this->returnValue($blockManager))
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, $this->blocksAdded, null, $this->blockManagersCollection);
        $this->slotManager->editBlock(2, array());
    }
    
    /**
     * @dataProvider editProvider
     */
    public function testEditNewBlock($blockManager)
    {
        $values = array('foo' => 'bar');
        
        if (null === $blockManager) {
            $this->blocksAdded
                ->expects($this->never())
                ->method('edit')
            ;
        }
        else {
            $this->blocksAdded
                ->expects($this->once())
                ->method('edit')
                ->with($blockManager, $values)
            ;
        }
        
        $this->blockManagersCollection
            ->expects($this->once())
            ->method('getBlockManager')
            ->with(2)
            ->will($this->returnValue($blockManager))
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, $this->blocksAdded, null, $this->blockManagersCollection);
        $this->slotManager->editBlock(2, $values);
    }
    
    public function editProvider()
    {
        return array(
            array(
                null,
            ),            
            array(
                $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Block\BlockManagerInterface'),
            ),
        );
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteBlockFails()
    {
        $this->blocksRemover
            ->expects($this->once())
            ->method('remove')
            ->will($this->throwException(new \InvalidArgumentException))
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, null, $this->blocksRemover, $this->blockManagersCollection);
        $this->slotManager->deleteBlock(2);
    }
    
    public function testDeleteBlock()
    {   
        $idBlock = 2;
        $this->blocksRemover
            ->expects($this->once())
            ->method('remove')
            ->with($idBlock, $this->blockManagersCollection)
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, null, $this->blocksRemover, $this->blockManagersCollection);
        $this->slotManager->deleteBlock($idBlock);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteBlocksFails()
    {
        $this->blocksRemover
            ->expects($this->once())
            ->method('clear')
            ->will($this->throwException(new \InvalidArgumentException))
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, null, $this->blocksRemover, $this->blockManagersCollection);
        $this->slotManager->deleteBlocks();
    }
    
    public function testDeleteBlocks()
    {   
        $this->blocksRemover
            ->expects($this->once())
            ->method('clear')
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, null, $this->blocksRemover, $this->blockManagersCollection);
        $this->slotManager->deleteBlocks();
    }
    
    public function testSetUpBlockManagers()
    {   
        $block = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\Block');
        $blocks = array(
            $block,
        );
        
        $blockManager = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Block\BlockManagerInterface');
        
        $this->factory
            ->expects($this->once())
            ->method('createBlockManager')
            ->with($block)
            ->will($this->returnValue($blockManager))
        ;
        
        $this->blockManagersCollection
            ->expects($this->once())
            ->method('addBlockManager')
            ->with($blockManager)
        ;
        
        $this->slotManager = new SlotManager($this->slot, $this->blockRepository, $this->factory, null, null, $this->blockManagersCollection);
        $this->slotManager->setUpBlockManagers($blocks);
    }
}