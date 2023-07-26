<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Pulsar\Interfaces\TranslatorInterface;
use Traversable;

/**
 * Holds error messages generated by models, like validation errors.
 */
final class Errors implements IteratorAggregate, Countable, ArrayAccess
{
    private static ?TranslatorInterface $translator = null;
    /** @var Error[] */
    private array $stack = [];

    /**
     * Sets the global translator instance.
     */
    public static function setTranslator(TranslatorInterface $translator): void
    {
        self::$translator = $translator;
    }

    /**
     * Clears the global translator instance.
     */
    public static function clearTranslator(): void
    {
        self::$translator = null;
    }

    /**
     * Gets the translator.
     */
    public function getTranslator(): TranslatorInterface
    {
        if (!self::$translator) {
            self::$translator = new Translator();
        }

        return self::$translator;
    }

    /**
     * Adds an error message to the stack.
     *
     * @return $this
     */
    public function add(string $error, array $context = []): self
    {
        $message = $this->parse($error, $context);
        $this->stack[] = new Error($error, $context, $message);

        return $this;
    }

    /**
     * Gets all the error messages on the stack.
     *
     * @return string[]
     */
    public function all(): array
    {
        $messages = [];
        foreach ($this->stack as $error) {
            $messages[] = $error->getMessage();
        }

        return $messages;
    }

    /**
     * Gets an error for a specific parameter on the stack.
     *
     * @param string $value value we are searching for
     * @param string $param parameter name
     */
    public function find(string $value, string $param = 'field'): ?Error
    {
        foreach ($this->stack as $error) {
            $stackValue = $error->getContext()[$param] ?? null;
            if ($stackValue === $value) {
                return $error;
            }
        }

        return null;
    }

    /**
     * Checks if an error exists with a specific parameter on the stack.
     *
     * @param string $value value we are searching for
     * @param string $param parameter name
     */
    public function has(string $value, string $param = 'field'): bool
    {
        foreach ($this->stack as $error) {
            $stackValue = $error->getContext()[$param] ?? null;
            if ($stackValue !== $value) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Clears the error stack.
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->stack = [];

        return $this;
    }

    public function __toString(): string
    {
        return implode("\n", $this->all());
    }

    // ////////////////////////
    // Helpers
    // ////////////////////////

    /**
     * Formats an incoming error message.
     */
    private function sanitize(array|string $input): Error
    {
        $error = is_array($input) ? $input['error'] : $input;
        $context = $input['context'] ?? [];
        $message = $this->parse($error, $context);

        return new Error($error, $context, $message);
    }

    /**
     * Parses an error message before displaying it.
     */
    private function parse(string $error, array $context): string
    {
        return $this->getTranslator()->translate($error, $context, null);
    }

    // ////////////////////////
    // IteratorAggregate Interface
    // ////////////////////////

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->stack);
    }

    // ////////////////////////
    // Countable Interface
    // ////////////////////////

    /**
     * Get total number of models matching query.
     */
    public function count(): int
    {
        return count($this->stack);
    }

    // ///////////////////////////
    // ArrayAccess Interface
    // ///////////////////////////

    public function offsetExists($offset): bool
    {
        return isset($this->stack[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException("$offset does not exist on this error stack");
        }

        return $this->stack[$offset];
    }

    public function offsetSet($offset, $error): void
    {
        if (!is_numeric($offset)) {
            throw new \Exception('Can only perform set on numeric indices');
        }

        $this->stack[$offset] = $this->sanitize($error);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->stack[$offset]);
    }
}
