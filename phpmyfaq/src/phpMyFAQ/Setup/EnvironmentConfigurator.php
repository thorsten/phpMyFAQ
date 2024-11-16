<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use SplFileObject;
use Symfony\Component\HttpFoundation\Request;
use Tivie\HtaccessParser\Exception\SyntaxException;
use Tivie\HtaccessParser\Parser;

use const Tivie\HtaccessParser\Token\TOKEN_DIRECTIVE;

class EnvironmentConfigurator
{
    private string $rootFilePath;

    private string $htaccessPath;

    private string $serverPath;

    public function __construct(string $rootPath = '', private readonly ?Request $request = null)
    {
        $this->rootFilePath = $rootPath;
        $this->htaccessPath = $this->rootFilePath . '/.htaccess';
    }

    public function getRootFilePath(): string
    {
        return $this->rootFilePath;
    }

    public function getHtaccessPath(): string
    {
        return $this->htaccessPath;
    }

    public function getServerPath(): string
    {
        return $this->serverPath = $this->request->getPathInfo();
    }

    /**
     * @throws Exception
     */
    public function getRewriteBase(): string
    {
        $file = new SplFileObject($this->htaccessPath);
        $parser = new Parser();
        try {
            $htaccess = $parser->parse($file);
        } catch (SyntaxException $e) {
            throw new Exception('Syntax error in .htaccess file: ' . $e->getMessage());
        } catch (\Tivie\HtaccessParser\Exception\Exception $e) {
            throw new Exception('Error parsing .htaccess file: ' . $e->getMessage());
        }
        $rewriteBase = $htaccess->search('RewriteBase', TOKEN_DIRECTIVE);

        return $rewriteBase->getArguments()[0];
    }

    /**
     * Adjusts the RewriteBase in the .htaccess file for the user's environment to avoid errors with controllers.
     * Returns true, if the file was successfully changed.
     *
     * @throws Exception
     */
    public function adjustRewriteBaseHtaccess(): bool
    {
        if (!file_exists($this->htaccessPath)) {
            throw new Exception(sprintf('The %s file does not exist!', $this->htaccessPath));
        }

        $file = new SplFileObject($this->htaccessPath);
        $parser = new Parser();
        try {
            $htaccess = $parser->parse($file);
        } catch (SyntaxException $e) {
            throw new Exception('Syntax error in .htaccess file: ' . $e->getMessage());
        } catch (\Tivie\HtaccessParser\Exception\Exception $e) {
            throw new Exception('Error parsing .htaccess file: ' . $e->getMessage());
        }
        $rewriteBase = $htaccess->search('RewriteBase', TOKEN_DIRECTIVE);

        $rewriteBase->removeArgument($this->getRewriteBase());
        $rewriteBase->setArguments((array)$this->getServerPath());

        $output = (string) $htaccess;
        return file_put_contents($this->htaccessPath, $output);
    }
}
