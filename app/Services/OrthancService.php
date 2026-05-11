<?php
namespace App\Services;

use App\Core\Logger;

class OrthancService {
    private string $baseUrl;
    private ?string $username;
    private ?string $password;
    private int $timeout;

    public function __construct(string $baseUrl, ?string $username = null, ?string $password = null, int $timeout = 30) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    /**
     * Faz uma requisição HTTP para a API REST do Orthanc
     */
    private function request(string $endpoint, string $method = 'GET', array $data = null): array {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init($url);
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ['Accept: application/json']
        ];

        if ($this->username && $this->password) {
            $options[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        }

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        
        curl_close($ch);

        if ($response === false) {
            Logger::error("Orthanc API Error ($method $url): $error");
            return ['success' => false, 'error' => $error, 'code' => 0];
        }

        $decoded = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decoded, 'code' => $httpCode];
        }

        Logger::error("Orthanc API HTTP $httpCode ($method $url): " . ($decoded['Message'] ?? $response));
        return ['success' => false, 'error' => $decoded['Message'] ?? 'Erro HTTP ' . $httpCode, 'code' => $httpCode];
    }

    /**
     * Testa a conexão e retorna as informações do sistema (versão, etc)
     */
    public function ping(): array {
        return $this->request('/system');
    }

    /**
     * Retorna a lista de todos os estudos (expandidos)
     */
    public function getStudies(): array {
        return $this->request('/studies?expand');
    }

    /**
     * Retorna a contagem total de estudos
     */
    public function countStudies(): int {
        $res = $this->request('/studies');
        if ($res['success'] && is_array($res['data'])) {
            return count($res['data']);
        }
        return 0;
    }

    /**
     * Retorna detalhes de um estudo específico
     */
    public function getStudy(string $studyId): array {
        return $this->request("/studies/$studyId");
    }

    /**
     * Retorna a lista de pacientes
     */
    public function getPatients(): array {
        return $this->request('/patients?expand');
    }

    /**
     * Retorna a contagem total de pacientes
     */
    public function countPatients(): int {
        $res = $this->request('/patients');
        if ($res['success'] && is_array($res['data'])) {
            return count($res['data']);
        }
        return 0;
    }
}
