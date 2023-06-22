<?php

declare(strict_types=1);

namespace Hotaruma\PackagistSync;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hotaruma\PackagistSync\Exception\SyncPackageServicePackageNotFoundException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class SyncPackageService
{
    protected string $apiToken;
    protected ?string $packagistUsername;
    protected ?string $packageName;
    protected ?string $packagistDomain;
    protected ?string $composerJsonPath;
    protected ?string $githubRepositoryUrl;

    protected string $vendorName;
    protected array $composerJsonData;

    /**
     * @throws RuntimeException
     */
    public function createPackage(): void
    {
        $url = sprintf(
            'https://%s/api/create-package?username=%s&apiToken=%s',
            $this->getPackagistDomain(),
            $this->getPackagistUsername(),
            $this->getApiToken()
        );
        $data = [
            'repository' => [
                'url' => $this->getGithubRepositoryUrl()
            ]
        ];

        $response = $this->sendPost($url, $data);
        $this->checkResultStatus($response);
    }

    /**
     * @throws RuntimeException|SyncPackageServicePackageNotFoundException
     */
    public function updatePackage(): void
    {
        $url = sprintf(
            'https://%s/api/update-package?username=%s&apiToken=%s',
            $this->getPackagistDomain(),
            $this->getPackagistUsername(),
            $this->getApiToken()
        );
        $data = [
            'repository' => [
                'url' => sprintf('https://%s/packages/%s', $this->getPackagistDomain(), $this->getPackageName())
            ]
        ];

        $response = $this->sendPost($url, $data);
        $code = $response->getStatusCode();
        if ($code == 404) {
            throw new SyncPackageServicePackageNotFoundException($this->getPackageName());
        }

        $this->checkResultStatus($response);
    }

    /**
     * @throws RuntimeException
     */
    public function findPackageByVendorName(): bool
    {
        $url = sprintf(
            'https://%s/packages/list.json?vendor=%s',
            $this->getPackagistDomain(),
            $this->getVendorName()
        );
        $response = $this->sendGet($url);

        $body = $response->getBody()->getContents();
        $body = json_decode($body, true);

        if (empty($body["packageNames"])) {
            return false;
        }

        foreach ($body["packageNames"] as $packageName) {
            if ($this->getPackageName() == $packageName) {
                return true;
            }
        }

        return false;
    }


    /**
     * @throws RuntimeException
     */
    protected function sendPost(string $url, array $jsonData = []): ResponseInterface
    {
        $client = new Client();

        $options = [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $jsonData
        ];

        try {
            return $client->post($url, $options);
        } catch (GuzzleException) {
            throw new RuntimeException('An error occurred while updating the package.');
        }
    }

    /**
     * @throws RuntimeException
     */
    protected function sendGet(string $url): ResponseInterface
    {
        $client = new Client();

        try {
            return $client->get($url);
        } catch (GuzzleException) {
            throw new RuntimeException('An error occurred while updating the package.');
        }
    }

    /**
     * @param ResponseInterface $response
     * @return void
     * @throws RuntimeException
     */
    protected function checkResultStatus(ResponseInterface $response): void
    {
        $body = $response->getBody()->getContents();
        $body = json_decode($body, true);

        if (empty($body['status']) || $body['status'] != 'success') {
            $message = is_array($body['message']) ? array_shift($body['message']) : $body['message'];
            throw new RuntimeException($message ?: 'An error occurred while checking the result status.');
        }
    }


    /**
     * @return array
     * @throws RuntimeException
     */
    protected function getComposerJsonData(): array
    {
        if (isset($this->composerJsonData)) {
            return $this->composerJsonData;
        }

        $composerJsonPath = $this->getComposerJsonPath() . 'composer.json';

        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException('composer.json file not found');
        }

        return $this->composerJsonData = json_decode(file_get_contents($composerJsonPath), true);
    }

    /**
     * @param string $vendorName
     * @return $this
     */
    public function setVendorName(string $vendorName): self
    {
        $this->vendorName = $vendorName;
        return $this;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function getVendorName(): string
    {
        return $this->vendorName ??=
            explode('/', $this->getPackageName())[0] ??
            explode('/', $this->getComposerJsonData()['name'])[0] ??
            throw new RuntimeException('');
    }


    /**
     * @param string|null $apiToken
     * @return SyncPackageService
     */
    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    /**
     * @param string|null $packagistUsername
     * @return SyncPackageService
     */
    public function setPackagistUsername(?string $packagistUsername): self
    {
        $this->packagistUsername = $packagistUsername;
        return $this;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function getPackagistUsername(): string
    {
        return $this->packagistUsername ??= $this->getVendorName();
    }

    /**
     * @param string|null $packageName
     * @return SyncPackageService
     */
    public function setPackageName(?string $packageName): self
    {
        $this->packageName = $packageName;
        return $this;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function getPackageName(): string
    {
        return $this->packageName ??=
            $this->getComposerJsonData()['name'] ??
            throw new RuntimeException('Package name not found.');
    }

    /**
     * @param string|null $packagistDomain
     * @return SyncPackageService
     */
    public function setPackagistDomain(?string $packagistDomain): self
    {
        $this->packagistDomain = $packagistDomain;
        return $this;
    }

    /**
     * @return string
     */
    public function getPackagistDomain(): string
    {
        return $this->packagistDomain;
    }

    /**
     * @param string|null $composerJsonPath
     * @return SyncPackageService
     */
    public function setComposerJsonPath(?string $composerJsonPath): self
    {
        $this->composerJsonPath = '/' . trim($composerJsonPath, '/') . '/';
        return $this;
    }

    /**
     * @return string
     */
    public function getComposerJsonPath(): string
    {
        return $this->composerJsonPath;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function getGithubRepositoryUrl(): string
    {
        return $this->githubRepositoryUrl ?? throw new RuntimeException('Github repository url not found.');
    }

    /**
     * @param string|null $githubRepositoryUrl
     * @return SyncPackageService
     */
    public function setGithubRepositoryUrl(?string $githubRepositoryUrl): self
    {
        $this->githubRepositoryUrl = $githubRepositoryUrl;
        return $this;
    }
}
