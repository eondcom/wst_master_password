# wst_master_password

회원 계정으로 로그인 할 수 있는 마스터 패스워드 기능을 제공하는 애드온.

## 설치방법

./addons/wst_master_password 폴더에 파일을 추가합니다.

Git을 활용할 경우 좀 더 편리하게 설치하고, 업데이트 할 수 있습니다.
```
cd ./addons/
git clone https://github.com/wstackme/wst_master_password

# 업데이트 시
git pull origin
```

## 설정법

### 비밀번호 설정

비밀번호는 SHA1 으로 암호화 하여 입력하여야 합니다.

SHA1 암호화는 아래 링크에서 간편하게 진행할 수 있습니다.

https://emn178.github.io/online-tools/sha1.html

#### 애드온 내 간편 입력

https://github.com/wstackme/wst_admin_setting_toolset

위 애드온을 설치하여 더욱 간편하게 SHA1 암호화를 진행할 수 있습니다.

위 애드온을 설치한 후, 본 애드온(마스터 패스워드 애드온) 설정에서 평문을 입력한 뒤 입력창 옆에 추가된 '계산기 버튼'을 누르면 자동으로 SHA1 암호화를 진행해 줍니다.