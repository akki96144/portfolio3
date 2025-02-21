# SSL証明書の有効期限を自動取得する
タイトルの通り、SSL証明書の有効期限を自動取得するコードの実装です。
自分が複数のドメインを持っている場合、有効期限を全てチェックするのは大変です。このコードで都度期限を確認する手間を省くことができます。

## 工夫した点
### 1. cronでコードを自動実行するようにした。
cronを使用して、自分の好きなタイミング、頻度でコードを実行できるようにしました。これでチェックし忘れるという事態を防ぐことができます。

### 2. curlではなくphpの組み込み関数を使用した。
SSL証明書の有効期限は、curlでも取得できます(curl_setopt($ch, CURLOPT_CERTINFO, true))。しかしこれは環境によっては動作しないことがあります。今回私が使用した組み込み関数ではそういった心配がないため、どの環境でも使うことができます。

参考URL: https://curl.se/libcurl/c/CURLOPT_CERTINFO.html, "This option works only with the following TLS backends: GnuTLS, OpenSSL, Schannel and Secure Transport"

---

## 使用方法
### 1. crontabに入る
ターミナルで以下のコマンドを実行してください。

```sh
crontab -e
```

### 2. cronコマンドを入力する
crontabに入ったら"i"を押して編集モードに切り替えます。その後以下のコマンドを入力してください。例として毎分実行するcronを書いていますが、ここは自由に書き換えてください。

```
* * * * * /path/your/php /path/your/file/checkSSL.php
```

/path/your/phpの部分についてですが、ターミナルで"which php"で出たパスを入力してください。
cronを書き終えたらetcを押して:wqで保存します。

### 3. 権限を変更する
以下のコマンドを実行してください。

```sh
chmod u+x /path/your/file/checkSSL.php
```

### 4. 生成されたsslResult.txtを確認する
コードを実行するとsslResult.txtというテキストファイルが同じディレクトリに自動生成されると思います。ここでSSL証明書の有効期限を確認するようにしてください。
