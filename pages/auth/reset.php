<?php
$token = trim($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Capsule Beta</title>

<?php include ROOT_PATH.'includes/icon.php'; ?>
<link rel="stylesheet" href="/assets/css/Capsule.css">

<style>
.password-wrap{position:relative;display:inline-block;width:100%;max-width:100%;}
.password-wrap input{padding-right:38px;}
.password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;cursor:pointer;color:#777;padding:4px;display:flex;align-items:center;justify-content:center;}
.password-toggle:hover{color:#111;}
.eye-icon{display:flex;align-items:center;justify-content:center;}
.eye-icon svg{display:block;}
            .eye-icon{display:flex;align-items:center;justify-content:center;}
            .eye-icon svg{display:block;}
</style>
</head>
<body>

<?php include ROOT_PATH.'includes/header.php'; ?>

<div class="main-container">
<div class="top-row">
<div class="login-box">
<div class="login-page">

<h1>&nbsp;Reset Password</h1>

<?php if($token==""): ?>

<form id="forgotForm" style="margin-top:5px;margin-left:5px;">

<p>
Enter your account e-mail address below.
If an account exists, we'll send you a password reset link.
</p>

<div style="background:#fff8d8;border:1px solid #e3c766;color:#6b5500;padding:10px;font-size:12px;line-height:1.5;">
<strong>Warning:</strong>
If you did not add an e-mail address to your account,
there is no way to recover it.
</div>

<br>

<p>E-mail:</p>

<input
type="email"
name="email"
placeholder="E-mail Address"
required>

<input
type="submit"
value="Send"
style="width:70px;height:25px;font-family:'Trebuchet MS';">

<div style="font-size:12px;margin-top:10px;">
Remembered your password?
<a href="/auth/login">
Login
</a>
</div>

</form>

<?php else: ?>

<form id="resetPasswordForm" style="margin-top:5px;margin-left:5px;">

<input
type="hidden"
id="token"
value="<?=htmlspecialchars($token,ENT_QUOTES)?>">

<p>
Enter your new password below.
</p>

<br>

<p>New Password:</p>

<div class="password-wrap">
<input
type="password"
id="password"
placeholder="New Password"
minlength="6"
required>
<button type="button" class="password-toggle" aria-label="Show password"><span class="eye-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span></button>
</div>

<p>Confirm Password:</p>

<div class="password-wrap">
<input
type="password"
id="password2"
placeholder="Confirm Password"
minlength="6"
required>
<button type="button" class="password-toggle" aria-label="Show password"><span class="eye-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span></button>
</div>

<input
type="submit"
value="Reset"
style="width:70px;height:25px;font-family:'Trebuchet MS';">

</form>

<?php endif; ?>

</div>
</div>
</div>
</div>

<?php include ROOT_PATH.'includes/bottom.php'; ?>

<script>

const forgot=document.getElementById("forgotForm");

if(forgot){

forgot.addEventListener("submit",async function(e){

e.preventDefault();

const response=await fetch("/api/v1/auth/forgot",{
method:"POST",
headers:{
"Content-Type":"application/json"
},
body:JSON.stringify({
email:this.email.value.trim()
})
});

const result=await response.json();

alert(result.message);

if(response.ok){
this.reset();
}

});

}

document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', () => {
        const input = button.parentElement.querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
    });
});

const reset=document.getElementById("resetPasswordForm");

if(reset){

reset.addEventListener("submit",async function(e){

e.preventDefault();

const pass=document.getElementById("password").value;
const pass2=document.getElementById("password2").value;

if(pass!==pass2){
alert("Passwords do not match.");
return;
}

const response=await fetch("/api/v1/auth/reset",{
method:"POST",
headers:{
"Content-Type":"application/json"
},
body:JSON.stringify({
token:document.getElementById("token").value,
password:pass
})
});

const result=await response.json();

alert(result.message);

});

}

</script>

</body>
</html>