<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateTemplate extends Model
{
    use HasFactory;

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'uuid',
        'name',
        'description', // Optionnel mais utile pour la gestion interne
        'background_color',
        'border_color',
        'border_width',
        'primary_font',
        'accent_font',
        'text_color',
        'orientation',
        'bg_opacity',
        'show_qr',
        'show_signature',
        'show_logo',
        'logo_scale',
        'logo_top',
        'show_background',
        'background_image_scale',
        'background_opacity',
        'config', // JSON contenant les positions X, Y, tailles des textes
        'signature_path',
        'logo_path',
        'background_image_path',
        'is_active', // Pour activer/désactiver un template sans le supprimer
        'created_by',
    ];

    /**
     * Conversion automatique des types.
     */
    protected $casts = [
        'config'                 => 'array',
        'show_qr'                => 'boolean',
        'show_signature'         => 'boolean',
        'show_logo'              => 'boolean',
        'show_background'        => 'boolean',
        'is_active'              => 'boolean',
        'bg_opacity'             => 'integer',
        'border_width'           => 'integer',
        'logo_scale'             => 'integer',
        'logo_top'               => 'integer',
        'background_image_scale' => 'integer',
        'background_opacity'     => 'integer',
    ];

    /**
     * Valeurs par défaut pour les nouveaux modèles.
     */
    protected $attributes = [
        'orientation'        => 'landscape',
        'background_color'   => '#ffffff',
        'border_color'       => '#c5a059',
        'text_color'         => '#1e293b',
        'bg_opacity'         => 100,
        'border_width'       => 20,
        'show_qr'            => true,
        'show_signature'     => true,
        'is_active'          => true,
        'background_opacity' => 20,
    ];

    /**
     * Logique d'initialisation du modèle.
     */
    protected static function booted()
    {
        // Génération automatique d'un UUID à la création
        static::creating(function ($template) {
            $template->uuid = $template->uuid ?? (string) Str::uuid();
        });

        // Nettoyage automatique des fichiers physiques lors de la suppression du modèle
        static::deleted(function ($template) {
            foreach (['signature_path', 'logo_path', 'background_image_path'] as $path) {
                if ($template->$path) {
                    Storage::disk('media')->delete($template->$path);
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSEURS (Getters pour le Frontend et le PDF)
    |--------------------------------------------------------------------------
    */

    /**
     * URL publique du logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('media')->url($this->logo_path) : null;
    }

    /**
     * URL publique de la signature.
     */
    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::disk('media')->url($this->signature_path) : null;
    }

    /**
     * URL publique de l'image de fond.
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image_path ? Storage::disk('media')->url($this->background_image_path) : null;
    }

    /**
     * Retourne les couleurs avec le préfixe '#' pour le CSS si nécessaire.
     */
    public function getSafeBackgroundColorAttribute()
    {
        return Str::startsWith($this->background_color, '#') ? $this->background_color : "#{$this->background_color}";
    }

    /**
     * Méthode utilitaire pour fusionner la config avec des valeurs par défaut
     * pour éviter les erreurs "Undefined key" dans Blade.
     */
    public function getElementsConfigAttribute()
    {
        $default = [
            'title'    => ['text' => 'CERTIFICAT', 'size' => 40, 'top' => 25, 'letter_spacing' => 0, 'color' => null],
            'subtitle' => ['text' => 'Décerné à', 'size' => 18, 'top' => 40, 'letter_spacing' => 0, 'color' => null],
            'body'     => ['text' => 'Pour la réussite de...', 'size' => 16, 'top' => 60, 'letter_spacing' => 0, 'color' => null],
        ];

        return array_merge($default, $this->config ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Certificats émis avec ce modèle.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    /**
     * Créateur du template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (Filtres réutilisables)
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLandscape($query)
    {
        return $query->where('orientation', 'landscape');
    }
}
