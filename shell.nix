{ pkgs ? import <nixpkgs> { } }:
let
  php-dbus = pkgs.php74.buildPecl rec {
    pname = "php-dbus";
    version = "0.2.0";

    src = pkgs.fetchFromGitHub {
      owner = "derickr";
      repo = "pecl-dbus";
      rev = "315d17558c7614d02e923d898231e51c86a25789";
      sha256 = "1lbkc1kppi5fikqd42ch81nshlj4fm256g2b973i96cqshw1v5gs";
    };
    nativeBuildInputs = [ pkgs.pkg-config pkgs.which pkgs.dbus pkgs.libxml2 ];

    postInstall = ''
      mv $out/lib/php/extensions/dbus.so $out/lib/php/extensions/php-dbus.so
    '';
  };
in pkgs.stdenv.mkDerivation {
  name = "HueBle-workspace";
  buildInputs = [
    (pkgs.php74.withExtensions
      ({ enabled, all }: enabled ++ [ all.xdebug php-dbus ]))
    pkgs.php74Packages.composer
  ];
}
