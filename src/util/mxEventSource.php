<?php

declare(strict_types=1);

namespace Mxgraph\Util;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxEventSource
{
    /**
     * Class: mxEventSource.
     *
     * Base class for all event sources.
     *
     * Variable: eventListeners
     *
     * Holds the registered listeners.
     */
    public $eventListeners;

    /**
     * Function: addListener.
     *
     * Adds a listener for the given event name. Note that the method of the
     * listener object must have the same name as the event it's being added
     * for. This is different from other language implementations of this
     * class.
     *
     * @param mixed $name
     * @param mixed $listener
     */
    public function addListener($name, $listener): void
    {
        if (null == $this->eventListeners) {
            $this->eventListeners = [];
        }

        $this->eventListeners[] = $name;
        $this->eventListeners[] = $listener;
    }

    /**
     * Function: fireEvent.
     *
     * Fires the event for the specified name.
     *
     * @param mixed $event
     */
    public function fireEvent($event): void
    {
        if (null != $this->eventListeners) {
            $name = $event->getName();

            for ($i = 0; $i < \count($this->eventListeners); $i += 2) {
                if ($this->eventListeners[$i] == $name) {
                    $this->eventListeners[$i + 1]->{$name}($event);
                }
            }
        }
    }
}
