<?php
declare(strict_types = 1);

namespace T3G\Pagetemplates\Provider;

use Symfony\Component\Yaml\Yaml;

class TemplateProvider
{
    /**
     * Path to configuration directory
     *
     * @var string
     */
    private $configurationPath;

    /**
     * TemplateProvider constructor.
     *
     * @param string $configurationPath
     */
    public function __construct(string $configurationPath)
    {
        $this->configurationPath = $configurationPath;
    }

    /**
     * Get configuration from yaml file for specific template
     *
     * @param string $templateIdentifier
     * @return array
     */
    public function getTemplateConfiguration(string $templateIdentifier) : array
    {
        $templatePath = $this->configurationPath . '/structure/' . $templateIdentifier . '.yaml';
        if (file_exists($templatePath)) {
            $content = file_get_contents($templatePath);
            return Yaml::parse($content);
        } else {
            throw new \InvalidArgumentException('Template path ' . htmlspecialchars($templatePath) . ' does not exist.');
        }
    }

    /**
     * Get all templates from configuration path
     *
     * @return array
     */
    public function getTemplates() : array
    {
        $files = $this->getYamlFilesInFolder($this->configurationPath);
        $templates = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $templates = array_merge($templates, Yaml::parse($content));
        }
        return $templates;
    }

    /**
     * Get all yaml files in specific folder
     *
     * @param string $path
     * @return array
     */
    protected function getYamlFilesInFolder(string $path) : array
    {
        $path = rtrim($path, '/');
        $files = [];
        foreach (glob($path . '/*.yaml') as $file) {
            $files[] = $file;
        }
        return $files;
    }
}
