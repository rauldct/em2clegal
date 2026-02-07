<?php
/**
 * AI Article Generator - AJAX endpoint
 * Uses Anthropic Claude API to generate SEO-optimized articles
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

auth_require();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Get API key from settings
$api_key = get_setting('ai_api_key', '');
if (!$api_key) {
    echo json_encode(['success' => false, 'error' => 'No se ha configurado la API key de IA. Ve a Configuración para añadirla.']);
    exit;
}

$ai_provider = get_setting('ai_provider', 'anthropic');

if ($action === 'suggest_titles') {
    $topic = trim($input['topic'] ?? '');
    if (!$topic) {
        echo json_encode(['success' => false, 'error' => 'Introduce un tema.']);
        exit;
    }

    $prompt = "Eres un experto en SEO y marketing de contenidos para un despacho de abogados en España especializado en extranjería, nacionalidad, arraigo, visados, derecho laboral, derecho de familia y derecho penal.

El usuario quiere escribir un artículo sobre: \"$topic\"

Genera exactamente 5 títulos de artículos optimizados para SEO que puedan atraer tráfico orgánico de Google España. Los títulos deben:
- Incluir la keyword principal de forma natural
- Tener entre 50-65 caracteres
- Ser informativos y atractivos para el público objetivo (inmigrantes y extranjeros en España)
- Incluir el año 2026 cuando sea relevante
- Usar formato de guía, tutorial, o pregunta cuando sea apropiado

Para cada título, incluye también una meta description SEO (max 155 caracteres).

Responde SOLO en formato JSON válido, sin ningún texto adicional ni markdown. Formato exacto:
[
  {\"title\": \"Título 1\", \"meta_description\": \"Descripción 1\"},
  {\"title\": \"Título 2\", \"meta_description\": \"Descripción 2\"},
  {\"title\": \"Título 3\", \"meta_description\": \"Descripción 3\"},
  {\"title\": \"Título 4\", \"meta_description\": \"Descripción 4\"},
  {\"title\": \"Título 5\", \"meta_description\": \"Descripción 5\"}
]";

    $response = call_ai_api($api_key, $ai_provider, $prompt, 1000);

    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'Error al conectar con la API de IA. Verifica la API key.']);
        exit;
    }

    // Parse JSON from response
    $titles = json_decode($response, true);
    if (!$titles || !is_array($titles) || count($titles) < 1) {
        // Try extracting JSON from response if wrapped in other text
        if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
            $titles = json_decode($matches[0], true);
        }
        if (!$titles || !is_array($titles)) {
            echo json_encode(['success' => false, 'error' => 'Error al procesar la respuesta de la IA.', 'raw' => $response]);
            exit;
        }
    }

    echo json_encode(['success' => true, 'titles' => $titles]);

} elseif ($action === 'generate_article') {
    $title = trim($input['title'] ?? '');
    $meta_description = trim($input['meta_description'] ?? '');
    $category_id = (int)($input['category_id'] ?? 0);

    if (!$title) {
        echo json_encode(['success' => false, 'error' => 'Falta el título.']);
        exit;
    }

    $prompt = "Eres un abogado experto y redactor de contenidos legales para EMC2 Legal Abogados, un despacho de abogados en Madrid (España) especializado en extranjería, nacionalidad, arraigo, visados, derecho laboral, derecho de familia y derecho penal.

Escribe un artículo completo y profesional con el título: \"$title\"

Requisitos del artículo:
- Longitud: entre 1500 y 2500 palabras
- Tono: profesional pero accesible, dirigido a personas sin conocimientos legales
- Idioma: español de España
- Formato: HTML (usa etiquetas h2, h3, p, ul, ol, li, strong, em, blockquote)
- Estructura:
  1. Introducción breve y enganchadora (1-2 párrafos)
  2. 4-6 secciones con encabezados H2 claros
  3. Subsecciones H3 cuando sea necesario
  4. Listas de requisitos o pasos cuando sea apropiado
  5. Un blockquote con un consejo importante
  6. Conclusión con llamada a la acción mencionando EMC2 Legal
- SEO:
  - Usa la keyword principal del título de forma natural 3-5 veces
  - Incluye keywords secundarias relacionadas
  - Los H2 deben contener keywords relevantes
- Incluye datos actualizados de 2026 cuando sea posible
- NO uses markdown, solo HTML
- NO incluyas el título H1 (ya se muestra por separado)
- NO uses etiquetas html, head o body

Responde SOLO con el contenido HTML del artículo, sin ningún texto adicional.";

    $response = call_ai_api($api_key, $ai_provider, $prompt, 4000);

    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'Error al generar el artículo.']);
        exit;
    }

    // Clean response - remove any markdown code fences
    $content = $response;
    $content = preg_replace('/^```html?\s*/i', '', $content);
    $content = preg_replace('/\s*```\s*$/', '', $content);
    $content = trim($content);

    // Create draft post
    $slug = unique_slug($title);
    $excerpt = auto_excerpt($content);
    $user_id = $_SESSION['user_id'];

    $stmt = db()->prepare("INSERT INTO posts (title, slug, content, excerpt, meta_title, meta_description, status, author_id, category_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'draft', ?, ?, NOW(), NOW())");
    $meta_title = mb_strlen($title) > 60 ? mb_substr($title, 0, 57) . '...' : $title;
    $meta_title .= ' | EMC2 Legal';

    $stmt->execute([
        $title,
        $slug,
        $content,
        $excerpt,
        $meta_title,
        $meta_description,
        $user_id,
        $category_id ?: null
    ]);

    $post_id = db()->lastInsertId();

    echo json_encode([
        'success' => true,
        'post_id' => $post_id,
        'redirect' => ADMIN_URL . '/post-editor.php?id=' . $post_id
    ]);

} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
}

/**
 * Call AI API (supports Anthropic Claude and OpenAI)
 */
function call_ai_api(string $api_key, string $provider, string $prompt, int $max_tokens = 4000): string|false
{
    if ($provider === 'openai') {
        return call_openai($api_key, $prompt, $max_tokens);
    }
    return call_anthropic($api_key, $prompt, $max_tokens);
}

/**
 * Call Anthropic Claude API
 */
function call_anthropic(string $api_key, string $prompt, int $max_tokens): string|false
{
    $model = get_setting('ai_model', '') ?: 'claude-sonnet-4-5-20250929';

    $data = [
        'model' => $model,
        'max_tokens' => $max_tokens,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        error_log("Anthropic API error ($http_code): $response");
        return false;
    }

    $result = json_decode($response, true);
    return $result['content'][0]['text'] ?? false;
}

/**
 * Call OpenAI API
 */
function call_openai(string $api_key, string $prompt, int $max_tokens): string|false
{
    $model = get_setting('ai_model', '') ?: 'gpt-4o';

    $data = [
        'model' => $model,
        'max_tokens' => $max_tokens,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        error_log("OpenAI API error ($http_code): $response");
        return false;
    }

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? false;
}
