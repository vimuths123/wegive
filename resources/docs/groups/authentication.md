# Authentication

Authentication methods

## Issue a token (login)


This endpoint lets you login by issuing a token.

> Example request:

```bash
curl -X POST \
    "http://givelist.test/api/auth/token" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"john@example.com","password":"hunter2","device_name":"Johns iPhone"}'

```

```javascript
const url = new URL(
    "http://givelist.test/api/auth/token"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "john@example.com",
    "password": "hunter2",
    "device_name": "Johns iPhone"
}

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response => response.json());
```


> Example response (200, success):

```json
{
    "token": "7|5Ov08EjzGcmBPCoVDjCIKwxxNTW59zHaAL9XKqmA"
}
```
> Example response (422, failure):

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password field is required."
        ],
        "device_name": [
            "The device name field is required."
        ]
    }
}
```
<div id="execution-results-POSTapi-auth-token" hidden>
    <blockquote>Received response<span id="execution-response-status-POSTapi-auth-token"></span>:</blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-token"></code></pre>
</div>
<div id="execution-error-POSTapi-auth-token" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-token"></code></pre>
</div>
<form id="form-POSTapi-auth-token" data-method="POST" data-path="api/auth/token" data-authed="0" data-hasfiles="0" data-headers='{"Content-Type":"application\/json","Accept":"application\/json"}' onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-token', this);">
<h3>
    Request&nbsp;&nbsp;&nbsp;
        <button type="button" style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-tryout-POSTapi-auth-token" onclick="tryItOut('POSTapi-auth-token');">Try it out ⚡</button>
    <button type="button" style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-canceltryout-POSTapi-auth-token" onclick="cancelTryOut('POSTapi-auth-token');" hidden>Cancel</button>&nbsp;&nbsp;
    <button type="submit" style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-executetryout-POSTapi-auth-token" hidden>Send Request 💥</button>
    </h3>
<p>
<small class="badge badge-black">POST</small>
 <b><code>api/auth/token</code></b>
</p>
<h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
<p>
<b><code>email</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="email" data-endpoint="POSTapi-auth-token" data-component="body" required  hidden>
<br>
The email address of the user.</p>
<p>
<b><code>password</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="password" data-endpoint="POSTapi-auth-token" data-component="body" required  hidden>
<br>
The password of the user.</p>
<p>
<b><code>device_name</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="device_name" data-endpoint="POSTapi-auth-token" data-component="body" required  hidden>
<br>
The name of the device you are logging in with.</p>

</form>


## Revoke a token (logout)

<small class="badge badge-darkred">requires authentication</small>

This endpoint lets you logout by revoking the token you are authenticated with.

> Example request:

```bash
curl -X DELETE \
    "http://givelist.test/api/auth/token" \
    -H "Authorization: Bearer {YOUR_TOKEN}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://givelist.test/api/auth/token"
);

let headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response => response.json());
```


> Example response (204):

```json
<Empty response>
```
<div id="execution-results-DELETEapi-auth-token" hidden>
    <blockquote>Received response<span id="execution-response-status-DELETEapi-auth-token"></span>:</blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-auth-token"></code></pre>
</div>
<div id="execution-error-DELETEapi-auth-token" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-auth-token"></code></pre>
</div>
<form id="form-DELETEapi-auth-token" data-method="DELETE" data-path="api/auth/token" data-authed="1" data-hasfiles="0" data-headers='{"Authorization":"Bearer {YOUR_TOKEN}","Content-Type":"application\/json","Accept":"application\/json"}' onsubmit="event.preventDefault(); executeTryOut('DELETEapi-auth-token', this);">
<h3>
    Request&nbsp;&nbsp;&nbsp;
        <button type="button" style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-tryout-DELETEapi-auth-token" onclick="tryItOut('DELETEapi-auth-token');">Try it out ⚡</button>
    <button type="button" style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-canceltryout-DELETEapi-auth-token" onclick="cancelTryOut('DELETEapi-auth-token');" hidden>Cancel</button>&nbsp;&nbsp;
    <button type="submit" style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-executetryout-DELETEapi-auth-token" hidden>Send Request 💥</button>
    </h3>
<p>
<small class="badge badge-red">DELETE</small>
 <b><code>api/auth/token</code></b>
</p>
<p>
<label id="auth-DELETEapi-auth-token" hidden>Authorization header: <b><code>Bearer </code></b><input type="text" name="Authorization" data-prefix="Bearer " data-endpoint="DELETEapi-auth-token" data-component="header"></label>
</p>
</form>


## Create Account


This endpoint lets you register a new account.

> Example request:

```bash
curl -X POST \
    "http://givelist.test/api/auth/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"first_name":"maxime","last_name":"maxime","email":"spencer07@example.org","password":"maxime"}'

```

```javascript
const url = new URL(
    "http://givelist.test/api/auth/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "first_name": "maxime",
    "last_name": "maxime",
    "email": "spencer07@example.org",
    "password": "maxime"
}

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response => response.json());
```


<div id="execution-results-POSTapi-auth-register" hidden>
    <blockquote>Received response<span id="execution-response-status-POSTapi-auth-register"></span>:</blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-register"></code></pre>
</div>
<div id="execution-error-POSTapi-auth-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-register"></code></pre>
</div>
<form id="form-POSTapi-auth-register" data-method="POST" data-path="api/auth/register" data-authed="0" data-hasfiles="0" data-headers='{"Content-Type":"application\/json","Accept":"application\/json"}' onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-register', this);">
<h3>
    Request&nbsp;&nbsp;&nbsp;
        <button type="button" style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-tryout-POSTapi-auth-register" onclick="tryItOut('POSTapi-auth-register');">Try it out ⚡</button>
    <button type="button" style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-canceltryout-POSTapi-auth-register" onclick="cancelTryOut('POSTapi-auth-register');" hidden>Cancel</button>&nbsp;&nbsp;
    <button type="submit" style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-executetryout-POSTapi-auth-register" hidden>Send Request 💥</button>
    </h3>
<p>
<small class="badge badge-black">POST</small>
 <b><code>api/auth/register</code></b>
</p>
<h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
<p>
<b><code>first_name</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="first_name" data-endpoint="POSTapi-auth-register" data-component="body" required  hidden>
<br>
</p>
<p>
<b><code>last_name</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="last_name" data-endpoint="POSTapi-auth-register" data-component="body" required  hidden>
<br>
</p>
<p>
<b><code>email</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="email" data-endpoint="POSTapi-auth-register" data-component="body" required  hidden>
<br>
The value must be a valid email address.</p>
<p>
<b><code>password</code></b>&nbsp;&nbsp;<small>string</small>  &nbsp;
<input type="text" name="password" data-endpoint="POSTapi-auth-register" data-component="body" required  hidden>
<br>
</p>

</form>


## Get Profile

<small class="badge badge-darkred">requires authentication</small>

Retrieve a user's profile.

> Example request:

```bash
curl -X GET \
    -G "http://givelist.test/api/users/profile" \
    -H "Authorization: Bearer {YOUR_TOKEN}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://givelist.test/api/users/profile"
);

let headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response => response.json());
```


> Example response (200):

```json
{
    "data": {
        "first_name": "Mike",
        "last_name": "Connelly",
        "avatar": ""
    }
}
```
<div id="execution-results-GETapi-users-profile" hidden>
    <blockquote>Received response<span id="execution-response-status-GETapi-users-profile"></span>:</blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-users-profile"></code></pre>
</div>
<div id="execution-error-GETapi-users-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-users-profile"></code></pre>
</div>
<form id="form-GETapi-users-profile" data-method="GET" data-path="api/users/profile" data-authed="1" data-hasfiles="0" data-headers='{"Authorization":"Bearer {YOUR_TOKEN}","Content-Type":"application\/json","Accept":"application\/json"}' onsubmit="event.preventDefault(); executeTryOut('GETapi-users-profile', this);">
<h3>
    Request&nbsp;&nbsp;&nbsp;
        <button type="button" style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-tryout-GETapi-users-profile" onclick="tryItOut('GETapi-users-profile');">Try it out ⚡</button>
    <button type="button" style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-canceltryout-GETapi-users-profile" onclick="cancelTryOut('GETapi-users-profile');" hidden>Cancel</button>&nbsp;&nbsp;
    <button type="submit" style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;" id="btn-executetryout-GETapi-users-profile" hidden>Send Request 💥</button>
    </h3>
<p>
<small class="badge badge-green">GET</small>
 <b><code>api/users/profile</code></b>
</p>
<p>
<label id="auth-GETapi-users-profile" hidden>Authorization header: <b><code>Bearer </code></b><input type="text" name="Authorization" data-prefix="Bearer " data-endpoint="GETapi-users-profile" data-component="header"></label>
</p>
</form>



