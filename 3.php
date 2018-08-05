<?php

namespace AppAdmin\Hydrator;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Zend\Hydrator\HydratorInterface;
use App\Model\Entity\EntityInterface;
use Doctrine\Common\Collections\Collection as CollectionInterface;
use AppAdmin\Hydrator\ExtractableInterface;

class RecursiveDecorator implements HydratorInterface
{
    /**
     * @var DoctrineObject
     */
    protected $hydrator;

    /**
     * @var array
     */
    protected $map;

    /**
     * RecursiveDecorator constructor.
     *
     * @param DoctrineObject $hydrator
     * @param array $map
     */
    public function __construct(DoctrineObject $hydrator, array $map)
    {
        $this->hydrator = $hydrator;
        $this->map = $map;
    }

    /**
     * @param array $data
     * @param object $object
     *
     * @return object
     */
    public function hydrate(array $data, $object)
    {
        return $this->hydrator->hydrate($data, $object);
    }

    /**
     * @param object $object
     * @param array $path
     *
     * @return array
     */
    public function extract($object, $path = [])
    {
        $data = $this->hydrator->extract($object);
        $map = $this->getMapFor($path);
        $result = [];

        if (in_array(ExtractableInterface::class, class_implements($object))) {
            $result = array_merge($result, $object->extract());
        }

        foreach ($data as $name => $value) {
            if (is_object($value)) {
                if (in_array($name, $map)) {
                    $result[$name] = $this->extractComplex(
                        $value,
                        array_merge($path, [$name])
                    );
                } else {
                    $result[$name] = $this->extractSimple($value);
                }
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * @param object $object
     * @param array $currentPath
     *
     * @return array|object
     */
    protected function extractComplex($object, $currentPath)
    {
        $result = null;

        if ($object instanceof EntityInterface) {
            $result = $this->extract($object, $currentPath);

            $result = array_merge($result, [get_class($object)]);
        } else if ($object instanceof CollectionInterface) {
            $result = [];

            foreach ($object as $i => $item) {
                $result[$i] = $this->extract($item, $currentPath);
            }
        }

        return $result;
    }

    /**
     * @param object $object
     *
     * @return array|object|mixed
     */
    protected function extractSimple($object)
    {
        if ($object instanceof EntityInterface) {
            $result = $object->getId();
        } else if ($object instanceof CollectionInterface) {
            $result = [];

            foreach ($object as $item) {
                $result[] = $item->getId();
            }
        } else {
            $result = $object;
        }

        return $result;
    }

    /**
     * @param array $path
     *
     * @return array
     */
    protected function getMapFor(array $path)
    {
        if (empty($path)) {
            return array_keys($this->map);
        }

        $localMap = $this->map;

        foreach ($path as $property) {
            if (!is_array($localMap[$property])) {
                break;
            }

            $localMap = $localMap[$property];
        }

        return array_keys($localMap);
    }
}
