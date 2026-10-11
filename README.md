## Reservas Acadêmicas

O **Reservas Acadêmicas** é um sistema para gerenciamento e reserva de ambientes e recursos acadêmicos, como salas,
laboratórios, auditórios, bancadas, computadores e equipamentos. A plataforma permite consultar a disponibilidade dos
recursos, solicitar e cancelar reservas, acompanhar aprovações e gerenciar ambientes de acordo com o perfil do usuário
(discente, docente ou técnico).

Documentação completa na [wiki do projeto](https://github.com/edu-almeidaf/reservas-academicas/wiki).

### Dependências

- Docker
- Docker Compose

As portas `80`, `8080` e `4444` precisam estar livres.

### Como rodar

#### Clonar o repositório

```
$ git clone https://github.com/edu-almeidaf/reservas-academicas.git
$ cd reservas-academicas
```

#### Definir as variáveis de ambiente

```
$ cp .env.example .env
```

#### Instalar as dependências

```
$ ./run composer install
$ ./run codecept build
```

#### Subir os containers

```
$ ./run up -d
```

#### Criar o banco de dados e as tabelas

```
$ ./run db:reset
```

Logo após o `up`, o MySQL pode levar alguns segundos para aceitar conexões. Se o comando falhar, aguarde e rode de novo.

#### Popular o banco de dados

```
$ ./run db:populate
```

Acesse [localhost](http://localhost).

### Dados de acesso

O populate cria um usuário de cada perfil:

| Perfil | E-mail | Senha | Área |
|--------|--------|-------|------|
| Discente | `discente@utfpr.edu.br` | `12345678` | Área do Discente (`/student`) |
| Docente | `docente@utfpr.edu.br` | `12345678` | Área do Docente (`/teacher`) |
| Técnico (admin) | `tecnico@utfpr.edu.br` | `12345678` | Área Administrativa (`/admin`) |

Novos discentes e docentes podem se cadastrar em [localhost/signup](http://localhost/signup). Usuários técnicos
(administradores) são criados somente pelo populate.

### Testes

Testes unitários e de integração ([PHPUnit](https://phpunit.de/)):

```
$ ./run test
```

Testes de aceitação no navegador ([Codeception](https://codeception.com/) + Selenium):

```
$ ./run test:browser
```

### Qualidade de código

[PHPCS](https://github.com/PHPCSStandards/PHP_CodeSniffer/)

```
$ ./run phpcs
```

[PHPStan](https://phpstan.org/)

```
$ ./run phpstan
```

### Parar a aplicação

```
$ ./run down
```
