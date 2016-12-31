<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Provider;


use Symfony\Component\Yaml\Yaml;

class TemplateProvider
{
    /**
     * @var string
     */
    private $configurationPath;

    public function __construct(string $configurationPath)
    {
        $this->configurationPath = $configurationPath;
    }

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

    public function getTemplates()
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
