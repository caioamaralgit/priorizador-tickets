# Priorizador de Tickets

Desafio proposto pela NeoAssist, como forma de avaliação técnica dos candidatos à vaga de **Programador Backend**, neste caso para a [vaga de *Pessoa Desenvolvedora PHP*](https://neoassist.gupy.io/jobs/50668?jobBoardSource=gupy_public_page).

* [Desafio](#desafio-proposto)
* [Solução](#solução)
* [Documentação](#documentação)
* [Faça o Teste!](#faça-o-teste)

---

## Desafio proposto

*Precisamos melhorar o atendimento no Brasil, para alcançar esse resultado, precisamos de um algoritmo que classifique nossos tickets (disponível em tickets.json) por uma ordem de prioridade, um bom parâmetro para essa ordenação é identificar o humor do consumidor.*

*Pensando nisso, queremos classificar nossos tickets com as seguintes prioridade: Normal e Alta.*

#### São exemplos:

##### Prioridade Alta:
- Consumidor insatisfeito com produto ou serviço
- Prazo de resolução do ticket alta
- Consumidor sugere abrir reclamação como exemplo Procon ou ReclameAqui
    
##### Prioridade Normal
- Primeira iteração do consumidor
- Consumidor não demostra irritação

Considere uma classificação com uma assertividade de no mínimo 70%, e guarde no documento (Nosso json) a prioridade e sua pontuação.

##### Com base nisso, você precisará desenvolver:
- Um algoritmo que classifique nossos tickets
- Uma API que exponha nossos tickets com os seguintes recursos
  - Ordenação por: Data Criação, Data Atualização e Prioridade
  - Filtro por: Data Criação (intervalo) e Prioridade
  - Paginação
        
##### Escolha as melhores ferramentas para desenvolver o desafio, as únicas regras são:
- Você deverá fornecer informações para que possamos executar e avaliar o resultado;
- Poderá ser utilizado serviços pagos (Mas gostamos bastante de projetos open source)
    
##### Critérios de avaliação
- Organização de código;
- Lógica para resolver o problema (Criatividade);
- Performance
    
##### Como entregar seu desafio
- Faça um Fork desse projeto, desenvolva seu conteúdo e informe no formulário (https://goo.gl/forms/5wXTDLI6JwzzvOEg2) o link do seu repositório

---

## Solução 

Para resolver o desafio foi utilizado o [framework Laravel](https://laravel.com/) (versão 5.8.26), banco de dados [Redis](https://redis.io/) (versão 3.0.504 para Windows) e um banco de dados [MySQL](https://www.mysql.com/) (versão 8.0.13).

Abaixo explico a resolução para as duas implementações solicitadas.

#### Algoritmo

O algoritmo de classificação foi montado considerando 3 critérios principais de avaliação. Cada item tem um peso diferente na pontuação do ticket, sendo que **quanto maior a pontuação, maior a prioridade**. Os três itens são descritos a seguir: 

1.  **Quantidade de tickets em aberto:** *(Peso 10 - Occurrence)*

    Durante o processo de execução, o programa faz uma iteração por cada registro do arquivo JSON e salva uma variável no *Redis* com o nome *customer:\<id-do-cliente>* somando o valor 1 a esse registro. 
    
    Por exemplo, o cliente de ID 123 possui 5 tickets em aberto. Na primeira vez que passar pelo ID 123, o software guarda no Redis o registro *customer:123* com o valor *1*. Quando passar novamente por esse cliente, o software altera o registro *customer:123* para *2* e depois *3*, e assim sucessivamente. Ao final da iteração o registro terá o valor *5*.

    Quando o algoritmo fizer os cálculos da pontuação de um ticket do cliente 123, ele encontrará um registro no Redis com o valor 5. O algoritmo então multiplica esse valor pelo peso da quantidade de tickets em aberto (occurrences), que foi configurado para 10. Ou seja, cada ticket desse cliente receberá uma soma de **50 pontos** em seu score.

2.  **Tempo de demora para fechamento:** *(Peso 0,0003 - Time)*

    Nesse item é avaliado o tempo entre a data de criação do ticket até a data da última atualização (não é considerado a data e hora atual). É feita a diferença entre essas duas datas e o resultado é retornado em minutos.

    Devido ao valor alto retornado na maioria dos cálculos, usa-se o peso 0,0003 para esse item.

    Em um dos tickets a data de criação é **"2017-12-21 03:51:39"** e a da última atualização é **"2018-01-01 06:21:59"**. Isso gera uma diferença de aproximadamente *15.990 minutos*. 15990 x 0,0003 = 4,797 pontos adicionados ao score do ticket. 

3.  **Humor do cliente:** *(Peso 20 - Words)*

    Entende-se que o humor do cliente é o fator mais importante para a priorização do atendimento, então o peso desse item é de 20 pontos.

    Para calcular os pontos, o algoritmo consulta o arquivo *config/words.json*, que contém um dicionário de **palavras boas** e **palavras ruins**. O algoritmo carrega esse dicionário ao ser instanciado.

    Quando analisa um ticket, o algoritmo lê todas as interações daquele ticket, e caso a iteração seja de um cliente, ele verifica o assunto e o texto da mensagem. Para cada palavra ruim encontrada nesses campos, ele **adiciona 1 ponto**. Para cada palavra boa encontrada, ele **retira 1 ponto**. 

    Ao final, a soma de todas as interações do cliente é multiplicada por 20 e adicionada ao score final do ticket.

Terminada a análise dos três itens o score do ticket é a soma de cada pontuação:

`(Item 1 * PO) + (Item 2 * PT) + (Item 3 * PW) = Final score`

*Caso a pontuação seja maior ou igual a **50 pontos**, o ticket receberá a prioridade alta. Do contrário será priorizado como normal.*

##### Pontos importantes em minha interpretação

* Entendo que o critério de priorização não é relativo aos registros do JSON, mas sim ao padrão estabelecido pela empresa. Ou seja, independente das datas dos registros fornecidos, o peso usado para calcular o tempo de resposta é o mesmo. O fato de um ticket ter um tempo aberto muito maior, não influencia na classificação de um outro.

* Assumo que todos os tickets no arquivo são tickets abertos. Assim todos os tickets de um mesmo cliente receberão maior prioridade, ainda que um tenha sido aberto antes que outro. Se um cliente tem 3 tickets em aberto, o algoritmo somará (3 x Peso de Ticket Aberto) em cada um dos três. O primeiro ticket não recebe maior prioridade nesse quesito, mas pode receber no quesito tempo.

* No arquivo recebido de exemplo há um tempo grande entre a data de criação e de atualização na maioria dos registros. Supõe-se então que a maioria dos tickets tenham, em média, o mesmo tempo de resolução.

* Conforme conversado com o recrutador, o tipo de análise que seria realizada ficaria a meu critério e a "assertividade" dependeria da forma que eu realizasse o exercício. Sendo assim, a marca de 50 pontos para prioridade alta foi determinada por mim arbitrariamente.

* Imagino um cenário onde há um banco de dados que guarde as informações dos tickets e interações dos clientes, e que a API não retorna os dados diretamente do arquivo JSON (o que seria inviável em produção).

* No sistema construído é possível registrar os tickets e interações durante o processo de classificação utilizando a opção *--save*, porém essa operação gera uma demora significativa na execução. Deve-se considerar o uso dessa opção de acordo com as circunstâncias e quantidade de registros.


#### API

A API foi feita de forma simples. Não há nenhum sistema de autenticação na API, embora pudesse ser implementado, caso fosse solicitado. 

Ela pode ser acessada através do caminho `/api/tickets`. Ao ser chamada, faz uma leitura no banco de dados MySQL onde estão armazenados os tickets e interações e os retorna em um JSON.

Há diversos parâmetros que podem ser passados e, **caso algum parâmetro seja passado com valor inválido, ele será desconsiderado.**

---

## Documentação

### Algoritmo

O algoritmo é executado através do comando abaixo.

```
php artisan classificate <file.json> <output.json> --save
```

Informações sobre o comando:

* `php artisan`:  Faz a leitura do arquivo *artisan*, na raiz do projeto, através do PHP.
* `classificate`: Chama o comando que inicia o algoritmo de classificação.
* `<file.json>`: *Argumento obrigatório.* O caminho relativo ou absoluto do arquivo a ser lido. É necessário o uso de aspas, caso o caminho contenha espaços em branco. 
* `<output.file>`: *Argumento opcional.* O caminho relativo ou absoluto do arquivo JSON com o score e prioridade, que deve ser exportado ao final da classificação. É necessário o uso de aspas, caso o caminho contenha espaços em branco. Caso esse argumento não seja passado, o algoritmo sobrescreverá o arquivo de entrada, adicionando o score e a prioridade. *Certifique-se de o processo tem permissão de escrita na pasta.*
* `--save` ou `-s`: *Parâmetro opcional.* Quando usado, faz com que o software grave no MySQL todos os tickets e interações, após realizar as classificações. Isso pode aumentar drasticamente o tempo de execução.

Exemplos de uso em um terminal Bash:

* Chamando o algoritmo de dentro da pasta do projeto, usando o arquivo da pasta *example*, exportando um novo arquivo na mesma pasta e gravando no MySQL:

```
php artisan classificate example/tickets.json example/new.tickets.json --save
```

* Chamando o algoritmo de fora da pasta do projeto, usando o arquivo da pasta *example*, exportando um novo arquivo na mesma pasta e gravando no MySQL:

```
php /c/priorizador-tickets/artisan C:/priorizador-tickets/example/tickets.js C:/priorizador-tickets/example/new.tickets.json -s
```

* Chamando o algoritmo de fora da pasta do projeto, usando e sobrescrevendo um arquivo em outra pasta:

```
php /c/priorizador-tickets/artisan C:/tickets.json
```

### API

A API é acessível no caminho `/api`, tendo somente a rota **GET** `/api/tickets`. Para acessá-la é necessário que a aplicação seja servida por um servidor web. Pode-se fazer isso através do servidor embutido do Laravel, com o comando `php artisan serve`. 
A rota **GET** `/api/tickets` retornará um JSON com o seguinte formato: 

```
{
  "current_page": 1,
  "data": [
    ...
  ],
  "first_page_url": "http://localhost:8000/api/tickets?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "http://localhost:8000/api/tickets?page=5",
  "next_page_url": "http://localhost:8000/api/tickets?page=2",
  "path": "http://localhost:8000/api/tickets",
  "per_page": 5,
  "prev_page_url": null,
  "to": 5,
  "total": 25
}
```

O campo **data** contém um array de objetos com todos os tickets que atendem os critérios passados. Os demais campos são auto-explicativos. Mais detalhes podem ser encontrados [aqui](https://laravel.com/docs/5.8/pagination#converting-results-to-json).

Esses são os parâmetros do recurso, passados via query string:

* `order`: *String*. Indica o campo que deve ser usado para ordenação dos registros. São valores possíveis: **created_at** (data de criação), **updated_at** (data de atualização) e **priority** (prioridade). *Caso a API não encontre ou não entenda o parâmetro, ela assumirá o valor **created_at**.*
* `direction`: *String*. Indica se os registros devem ser exibidos em ordem crescente ou decrescente. São valores possíveis: **ASC** (crescente) e **DESC** (decrescente). *Caso a API não encontre ou não entenda o parâmetro, ela assumirá o valor **ASC**.*
* `start_date`: *String*. Indica a data mínima de criação dos registros exibidos. A data deve ser passada no formato **YYYY-MM-DD**. *Caso a API não encontre ou não entenda o parâmetro, ela ignorará esse filtro.*
* `end_date`: *String*. Indica a data máxima de criação dos registros exibidos. A data deve ser passada no formato **YYYY-MM-DD**. *Caso a API não encontre ou não entenda o parâmetro, ou caso a data seja anterior à recebida no parâmetro `start_date`, ela ignorará esse filtro.*
* `priority`: *String*. Indica a prioridade dos registros exibidos. São valores possíveis: **Normal** e **Alta**. *Caso a API não encontre ou não entenda o parâmetro, ela ignorará esse filtro.*
* `limit`: *Integer*. Indica a quantidade de registros exibidos por página. O valor mínimo é 2. *Caso a API não encontre ou não entenda o parâmetro, ela assumirá o valor **5**.*
* `page`: *Integer*. Indica a página dos registros a serem exibidos. *Caso a API não encontre ou não entenda o parâmetro, ela assumirá o valor **1**.*

Todos os valores dos parâmetros são **case insentive**.

---

## Faça o teste

Para baixar e executar o sistema de acordo com este tutorial você precisa ter instalados:
* Git (somente para baixar o projeto) 
* PHP (pelo menos na versão 7.1.3)
* Composer
* Redis 
* MySQL

Primeiro baixe o projeto usando o Git em um terminal.

```
git clone git@github.com:caioamaralgit/priorizador-tickets.git
```

Acesse a pasta do projeto e renomeie o arquivo *.env.example* para *.env*.

```
cd priorizador-tickets
mv .env.example .env
```

Baixe então os pacotes do Composer.

```
composer install
```

Edite o arquivo *.env* e adicione os parâmetros corretos de conexão ao MySQL e ao Redis. Verifique se os dois serviços estão rodando. Também certifique-se de que o projeto tenha uma *APP_KEY* (você pode gerar essa chave com o comando `php artisan key:generate`).

Tendo feito isso, *crie manualmente a database em seu banco MySQL* com o nome configurado no arquivo *.env* e execute o comando artisan para criação das tabelas.

```
php artisan migrate
```

Após fazer isso, você poderá executar o algoritmo de classificação e salvar registros no banco. Para testar você pode utilizar o arquivo de exemplo *example/tickets.json*. Para isso use o comando:

```
php artisan classificate /caminho/da/pasta/example/tickets.json /caminho/da/pasta/example/new.tickets.json --save
```

Um novo arquivo com o nome *new.tickets.json* será criado na pasta *example* contendo o score e a classificação de cada ticket.

Por fim, podemos consultar os dados na API. Iniciamos um servidor com o comando:

```
php artisan serve
```

Um servidor será iniciado na porta 8000. Podemos então acessar a api no navegador com o link http://127.0.0.1:8000/api/tickets.

Pronto, você classificou os tickets e os consultou através de uma API.