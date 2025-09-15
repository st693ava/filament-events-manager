# 📋 Eventos Standard - Guia de Instalação e Uso

Este documento apresenta as regras de eventos pré-configuradas incluídas no **Filament Events Manager** que podem ser instaladas automaticamente para cobrir os casos de uso mais comuns.

## 🚀 Instalação Rápida

### Instalar Todas as Regras
```bash
php artisan events-manager:install-defaults
```

### Opções Disponíveis
```bash
# Visualizar o que será instalado sem executar
php artisan events-manager:install-defaults --dry-run

# Forçar substituição de regras existentes
php artisan events-manager:install-defaults --force

# Instalar apenas categorias específicas
php artisan events-manager:install-defaults --only=auth,security
```

### Categorias Disponíveis
- `auth` - Autenticação e registo de utilizadores
- `security` - Alertas e monitorização de segurança
- `audit` - Auditoria e compliance
- `errors` - Monitorização de erros críticos

---

## 📑 Regras Incluídas

### 🔐 Autenticação (auth)

#### 1. User Login Success - Activity Log
**Trigger:** `Illuminate\Auth\Events\Login`
**Ações:**
- Regista login no **spatie/activity-log** com dados completos
- Log name: `authentication`
- Event: `login`

**Dados registados:**
- Nome e email do utilizador
- IP address
- User agent
- Timestamp do login

**Exemplo de como disparar manualmente:**
```php
// O evento é disparado automaticamente pelo Laravel Auth
// Mas também podes disparar manualmente se necessário:
event(new \Illuminate\Auth\Events\Login('web', $user, false));
```

#### 2. User Registration - Welcome & Audit
**Trigger:** `Illuminate\Auth\Events\Registered`
**Ações:**
1. Regista no activity log
2. Envia email de boas-vindas (personalizável)

**Como disparar:**
```php
event(new \Illuminate\Auth\Events\Registered($user));
```

#### 3. Password Reset - Security Audit
**Trigger:** `Illuminate\Auth\Events\PasswordReset`
**Ações:**
1. Regista no activity log para auditoria
2. Envia email de confirmação de segurança

**Como disparar:**
```php
event(new \Illuminate\Auth\Events\PasswordReset($user));
```

### 🛡️ Segurança (security)

#### 4. Failed Login Attempts - Security Alert
**Trigger:** `Illuminate\Auth\Events\Failed`
**Ações:**
1. Regista tentativa falhada no activity log
2. Envia email de alerta de segurança (opcional)

**Log name:** `security`
**Event:** `login_failed`

**Como disparar:**
```php
event(new \Illuminate\Auth\Events\Failed('web', null, [
    'email' => 'user@example.com',
    'password' => 'attempted_password'
]));
```

#### 5. Suspicious Activity - Multiple Failed Logins
**Trigger:** `Illuminate\Auth\Events\Failed`
**Ações:**
- Regista atividade suspeita com nível de alerta elevado
- Log name: `security_alerts`

### 📊 Auditoria (audit)

#### 6. Data Export Audit
**Trigger:** Eloquent `retrieved` events (configurável)
**Estado:** Inativo por padrão (precisa de configuração específica)
**Ações:**
- Regista exportações para compliance
- Log name: `data_compliance`

### ⚠️ Erros (errors)

#### 7. Critical Application Errors
**Trigger:** `Illuminate\Log\Events\MessageLogged`
**Estado:** Inativo por padrão (para evitar spam)
**Ações:**
1. Envia email de alerta crítico
2. Regista no activity log com categoria de erro crítico

---

## 🔧 Personalização das Regras

### Configurar Emails de Destino

Após a instalação, podes personalizar os emails através do admin do Filament:

1. **Admin Panel** → **Events Manager** → **Event Rules**
2. Editar a regra desejada
3. Na secção **Actions**, alterar o campo `to` para o teu email:

```php
// Em vez de config('mail.from.address')
'to' => 'admin@tuaempresa.com'
```

### Personalizar Templates de Email

Exemplo de personalização do email de boas-vindas:

```php
'subject' => 'Bem-vindo à {app.name}, {user.name}!',
'body' => "Olá {user.name},\n\nA tua conta foi criada com sucesso em {app.name}.\n\nDetalhes da conta:\n- Email: {user.email}\n- Data de registo: {triggered_at}\n- IP de registo: {ip_address}\n\nSe precisares de ajuda, contacta-nos.\n\nObrigado!"
```

### Variáveis Disponíveis nos Templates

#### Dados do Utilizador
- `{user.name}` - Nome do utilizador
- `{user.email}` - Email do utilizador
- `{user.id}` - ID do utilizador

#### Dados de Contexto
- `{ip_address}` - Endereço IP
- `{user_agent}` - User Agent do browser
- `{triggered_at}` - Data/hora do evento
- `{app.name}` - Nome da aplicação

#### Dados de Autenticação Específicos
- `{credentials.email}` - Email da tentativa (para logins falhados)

---

## 🧪 Testar as Regras

### Testar Regra Específica
```bash
php artisan events:test-rule 1 --scenario=user_login
```

### Simular Login Falhado
```php
// No teu código ou tinker
event(new \Illuminate\Auth\Events\Failed('web', null, [
    'email' => 'teste@exemplo.com',
    'password' => 'password_errada'
]));
```

### Simular Registo de Utilizador
```php
$user = User::factory()->create();
event(new \Illuminate\Auth\Events\Registered($user));
```

---

## 📈 Monitorização

### Verificar Logs de Atividade
```php
use Spatie\Activitylog\Models\Activity;

// Ver logins recentes
$logins = Activity::where('log_name', 'authentication')
    ->where('event', 'login')
    ->latest()
    ->get();

// Ver tentativas falhadas
$failedAttempts = Activity::where('log_name', 'security')
    ->where('event', 'login_failed')
    ->latest()
    ->get();
```

### Dashboard do Filament

Acede ao dashboard em:
**Admin Panel** → **Events Manager** → **Dashboard**

Podes ver:
- Estatísticas de eventos
- Triggers recentes
- Performance das regras
- Logs de execução

---

## ⚙️ Configurações Avançadas

### Ativar/Desativar Regras

```php
// Desativar temporariamente emails de alerta
EventRule::where('name', 'Failed Login Attempts - Security Alert')
    ->update(['is_active' => false]);
```

### Configurar Rate Limiting

No ficheiro `config/filament-events-manager.php`:

```php
'security' => [
    'rate_limit_per_minute' => 60,
    'max_template_size' => 10240,
],
```

### Processamento Assíncrono

Para alto volume de eventos:

```php
'async_processing' => true,
'queue_name' => 'events',
'job_timeout' => 300,
```

---

## 🔄 Integração com o teu Código

### Disparar Eventos Personalizados

```php
// Para sistemas de login personalizado
if ($loginSuccessful) {
    event(new \Illuminate\Auth\Events\Login('web', $user, $remember));
} else {
    event(new \Illuminate\Auth\Events\Failed('web', $user, $credentials));
}
```

### Hook em Middleware Personalizado

```php
class SecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        // Tua lógica de segurança

        if ($suspiciousActivity) {
            event(new \Illuminate\Auth\Events\Failed('web', null, [
                'email' => $request->input('email'),
                'reason' => 'suspicious_activity_detected'
            ]));
        }

        return $next($request);
    }
}
```

---

## 🚨 Notas Importantes

### Regras Inativas por Padrão

Algumas regras são instaladas **inativas** para evitar problemas:

1. **Data Export Audit** - Precisa de configuração específica dos modelos
2. **Critical Application Errors** - Pode gerar muitos emails

### Configuração de Email Obrigatória

Certifica-te de que tens o email configurado no teu `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@tuaapp.com
MAIL_FROM_NAME="Tua App"
```

### Performance

Para aplicações com muito tráfego:
- Ativa processamento assíncrono
- Considera usar Redis para cache
- Monitoriza o tamanho dos logs

---

## ❓ FAQ

### Como adicionar campos personalizados aos logs?

Edita a ação `activity_log` da regra e adiciona propriedades:

```php
'properties' => [
    'custom_field' => '{user.custom_attribute}',
    'request_id' => '{request.id}',
]
```

### Como integrar com Slack?

Adiciona uma ação `webhook` com o webhook URL do Slack:

```php
'webhook_url' => 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK',
'payload' => [
    'text' => 'Alerta: {event_description}',
    'channel' => '#security'
]
```

### Como fazer backup das regras?

```bash
php artisan events:export-rules --format=json
```

---

**Precisas de mais regras personalizadas?** Consulta a documentação principal do package ou cria as tuas próprias regras através da interface do Filament! 🚀