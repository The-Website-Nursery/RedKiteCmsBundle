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

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\Unit\Core\Event\Content\Base;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\TestCase;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Event\Deploy\Base\BaseDeployEvent;

class DeployEventTester extends BaseDeployEvent
{
}

/**
 * DeployEventTest
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
class DeployEventTest extends TestCase
{
    private $deployer;

    public function testDeployerProperty()
    {
        $this->deployer = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Deploy\DeployerInterface');

        $this->event = new DeployEventTester($this->deployer);
        $deployer = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Deploy\DeployerInterface');
        $this->event->setDeployer($deployer);
        $this->assertSame($deployer, $this->event->getDeployer());        
        $this->assertNotSame($this->deployer, $this->event->getDeployer());
    }
}

