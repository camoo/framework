<?php
declare(strict_types=1);

namespace CAMOO\Event;

/**
 * Trait EventDispatcherTrait
 * @author CamooSarl
 */
trait EventDispatcherTrait
{
    protected $_eventClass = Event::class;
    protected $_eventManager;

    public function getEventManager()
    {
        if ($this->_eventManager === null) {
            $this->_eventManager = new EventManager();
        }

        return $this->_eventManager;
    }

    public function dispatchEvent($name, $data = null, $subject = null)
    {
        if ($subject === null) {
            $subject = $this;
        }

        $event = new $this->_eventClass($name, $subject, $data);
        $this->getEventManager()->dispatch($event);

        return $event;
    }

    public function setEventManager(EventManager $eventManager)
    {
        $this->_eventManager = $eventManager;

        return $this;
    }
}
