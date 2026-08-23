<?php

namespace phpMyFAQ\Attachment\Filesystem\File;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phpMyFAQ\Attachment\Filesystem\AbstractFile;
use PHPUnit\Framework\TestCase;

/**
 * Class EncryptedFileTest
 *
 * @package phpMyFAQ\Attachment\Filesystem\File
 */
class EncryptedFileTest extends TestCase
{
    /**
     * A 32 byte (AES-256) test key.
     */
    private const string KEY = '0123456789abcdef0123456789abcdef';

    private vfsStreamDirectory $vfsRoot;

    protected function setUp(): void
    {
        $this->vfsRoot = vfsStream::setup('attachments', 0777);
    }

    public function testConstructorAcceptsFilesystemWriteMode(): void
    {
        // Regression test: the fopen() mode must not be used as the AES cipher
        // mode, which threw a phpseclib3 BadModeException for 'wb'.
        $path = vfsStream::url('attachments/encrypted.bin');
        $encryptedFile = new EncryptedFile($path, AbstractFile::MODE_WRITE, self::KEY);

        $this->assertTrue($encryptedFile->isOk());
        $this->assertEquals(AbstractFile::MODE_WRITE, $encryptedFile->getMode());
    }

    public function testEncryptedRoundTrip(): void
    {
        $path = vfsStream::url('attachments/encrypted.bin');
        $plaintext = 'This is a secret attachment payload.';

        $writer = new EncryptedFile($path, AbstractFile::MODE_WRITE, self::KEY);
        $this->assertNotFalse($writer->putChunk($plaintext));
        unset($writer);

        $this->assertStringNotContainsString($plaintext, (string) file_get_contents($path));

        $reader = new EncryptedFile($path, AbstractFile::MODE_READ, self::KEY);
        $decrypted = '';
        while (!$reader->eof()) {
            $decrypted .= $reader->getChunk();
        }

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testCopyToDecryptsIntoVanillaFile(): void
    {
        $encryptedPath = vfsStream::url('attachments/encrypted.bin');
        $decryptedPath = vfsStream::url('attachments/decrypted.txt');
        $plaintext = 'Round trip via copyTo().';

        $writer = new EncryptedFile($encryptedPath, AbstractFile::MODE_WRITE, self::KEY);
        $writer->putChunk($plaintext);
        unset($writer);

        $reader = new EncryptedFile($encryptedPath, AbstractFile::MODE_READ, self::KEY);
        $target = new VanillaFile($decryptedPath, AbstractFile::MODE_WRITE);

        $this->assertTrue($reader->copyTo($target));
        unset($target);

        $this->assertEquals($plaintext, file_get_contents($decryptedPath));
    }

    public function testEncryptionFromVanillaSourceAsInSave(): void
    {
        // Mirrors File::save(): a VanillaFile source is moved into an
        // EncryptedFile target via copyTo()/putChunk().
        $sourcePath = vfsStream::url('attachments/upload.tmp');
        $targetPath = vfsStream::url('attachments/stored.bin');
        $plaintext = str_repeat('phpMyFAQ attachment content. ', 40);

        file_put_contents($sourcePath, $plaintext);

        $source = new VanillaFile($sourcePath);
        $target = new EncryptedFile($targetPath, AbstractFile::MODE_WRITE, self::KEY);

        $this->assertTrue($source->copyTo($target));
        unset($source, $target);

        $this->assertStringNotContainsString('phpMyFAQ', (string) file_get_contents($targetPath));

        $reader = new EncryptedFile($targetPath, AbstractFile::MODE_READ, self::KEY);
        $decrypted = '';
        while (!$reader->eof()) {
            $decrypted .= $reader->getChunk();
        }

        $this->assertEquals($plaintext, $decrypted);
    }
}
