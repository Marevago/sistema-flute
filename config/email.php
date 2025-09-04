<?php
// Importando as classes do PHPMailer que instalamos via Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carregando o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mailer;
/*************  ✨ Codeium Command ⭐  *************/
/**
 * Initializes the EmailService class by setting up a PHPMailer instance with SMTP configuration.
 *
 * This constructor configures the PHPMailer to use Gmail's SMTP server for sending emails.
 * It sets the necessary credentials and encryption protocols for secure email transmission.
 *
 * @throws Exception If there is an error in the email configuration, an exception is thrown with a relevant message.
 */

/******  c34172e2-a5cc-4a30-ac3e-065cb6a84f30  *******/    
public function __construct() {
    $this->mailer = new PHPMailer(true);
    
    try {
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'paulosschroeder@gmail.com'; // Verifique se está correto
        $this->mailer->Password = 'xoha hxyq ffnp znik'; // Verifique se está correta
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->CharSet = 'UTF-8';
        // Evita logs de debug em produção e limita o tempo de espera do SMTP
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Timeout = 10; // segundos
        
        $this->mailer->setFrom('paulosschroeder@gmail.com', 'Flute Incensos');
        
    } catch (Exception $e) {
        throw new Exception("Erro na configuração do email: " . $e->getMessage());
    }
}
    
    // Método que envia o email de boas-vindas
    public function enviarBoasVindas($emailDestinatario, $nomeDestinatario) {
        try {
            // Limpamos destinatários anteriores (por segurança)
            $this->mailer->clearAddresses();
            
            // Configuramos o novo destinatário
            $this->mailer->addAddress($emailDestinatario, $nomeDestinatario);
            
            // Configuramos o email
            $this->mailer->isHTML(true);  // Vamos usar HTML para deixar o email bonito
            $this->mailer->Subject = 'Bem-vindo ao Sistema Flute!';
            
            // O corpo do email em HTML (versão bonita)
            $this->mailer->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #333;'>Olá, {$nomeDestinatario}!</h2>
                    
                    <p>Que bom ter você conosco! Seu cadastro foi realizado com sucesso.</p>
                    
                    <p>Você já pode acessar nossa plataforma usando seu email:</p>
                    <p style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>
                        {$emailDestinatario}
                    </p>
                    
                    <div style='margin: 30px 0; text-align: center;'>
                        <a href='http://localhost/sistema_flute/login.html' 
                           style='background-color: #4CAF50; 
                                  color: white; 
                                  padding: 12px 25px; 
                                  text-decoration: none; 
                                  border-radius: 5px;'>
                            Acessar o Sistema
                        </a>
                    </div>
                    
                    <p>Se precisar de ajuda, é só responder este email.</p>
                    
                    <p>Abraços,<br>Equipe Sistema Flute</p>
                </div>
            ";
            
            // Versão em texto simples (para clientes de email mais antigos)
            $this->mailer->AltBody = "
                Olá, {$nomeDestinatario}!
                
                Que bom ter você conosco! Seu cadastro foi realizado com sucesso.
                
                Você já pode acessar nossa plataforma usando seu email: {$emailDestinatario}
                
                Para acessar o sistema, visite: http://localhost/sistema_flute/login.html
                
                Se precisar de ajuda, é só responder este email.
                
                Abraços,
                Equipe Sistema Flute
            ";
            
            // Enviamos o email
            return $this->mailer->send();
            
        } catch (Exception $e) {
            throw new Exception("Erro ao enviar email: " . $e->getMessage());
        }
    }
    public function enviarPedidoAdmin($corpo_email) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress('paulosschroeder@gmail.com', 'Admin Loja'); // Seu email aqui
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Novo Pedido Recebido';
            $this->mailer->Body = $corpo_email;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            throw new Exception("Erro ao enviar email: " . $e->getMessage());
        }
    }
    
}
?>