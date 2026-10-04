<?php

namespace App\Models\Traits;

use App\Support\EncryptedId;

trait HasEncryptedId
{
    /**
     * Cached encrypted ID for this model instance.
     *
     * @var string|null
     */
    protected ?string $memoizedEncryptedId = null;

    /**
     * Get the encrypted ID for the model.
     *
     * @return string
     */
    public function getEncryptedIdAttribute(): string
    {
        if ($this->memoizedEncryptedId === null) {
            $this->memoizedEncryptedId = EncryptedId::encrypt($this->getKey());
        }

        return $this->memoizedEncryptedId;
    }

    /**
     * Get the route key for the model.
     * Overrides Eloquent's default getRouteKey() so that route('...', $model)
     * automatically uses the encrypted ID in URLs.
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->encrypted_id;
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $id = EncryptedId::decrypt($value);
        return $this->where($field ?? $this->getRouteKeyName(), $id)->firstOrFail();
    }
}
