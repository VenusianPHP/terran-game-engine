<?php

namespace ScrapyardIO\GameEngine\Resources;

use PDO;

/**
 * SQLite-backed texture / asset cache.
 */
class ResourceStore
{
    protected PDO $pdo;

    public function __construct(?string $databasePath = null)
    {
        $path = $databasePath ?? sys_get_temp_dir().'/scrapyard-game-engine-resources.sqlite';
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->pdo = new PDO('sqlite:'.$path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->migrate();
    }

    protected function migrate(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS textures (
                path TEXT PRIMARY KEY,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                rgba BLOB NOT NULL,
                updated_at TEXT NOT NULL
            )'
        );
    }

    public function putTexture(string $path, ImageTexture $texture): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO textures (path, width, height, rgba, updated_at)
             VALUES (:path, :width, :height, :rgba, :updated_at)
             ON CONFLICT(path) DO UPDATE SET
                width = excluded.width,
                height = excluded.height,
                rgba = excluded.rgba,
                updated_at = excluded.updated_at'
        );

        $stmt->execute([
            'path' => $path,
            'width' => $texture->width(),
            'height' => $texture->height(),
            'rgba' => $texture->rgba(),
            'updated_at' => gmdate('c'),
        ]);
    }

    public function getTexture(string $path): ?ImageTexture
    {
        $stmt = $this->pdo->prepare('SELECT width, height, rgba FROM textures WHERE path = :path');
        $stmt->execute(['path' => $path]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new ImageTexture((int) $row['width'], (int) $row['height'], (string) $row['rgba']);
    }

    public function has(string $path): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM textures WHERE path = :path');
        $stmt->execute(['path' => $path]);

        return $stmt->fetchColumn() !== false;
    }
}
