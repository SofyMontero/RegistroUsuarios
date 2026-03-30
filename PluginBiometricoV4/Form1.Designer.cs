namespace PluginBiometricoV4;

partial class Form1
{
    /// <summary>
    ///  Required designer variable.
    /// </summary>
    private System.ComponentModel.IContainer components = null;

    /// <summary>
    ///  Clean up any resources being used.
    /// </summary>
    /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
    protected override void Dispose(bool disposing)
    {
        if (disposing && (components != null))
        {
            components.Dispose();
        }
        base.Dispose(disposing);
    }

    #region Windows Form Designer generated code

    /// <summary>
    ///  Required method for Designer support - do not modify
    ///  the contents of this method with the code editor.
    /// </summary>
    private void InitializeComponent()
    {
        this.components = new System.ComponentModel.Container();
        this.lblEstado = new System.Windows.Forms.Label();
        this.SuspendLayout();
        //
        // lblEstado
        //
        this.lblEstado.AutoSize = false;
        this.lblEstado.Dock = System.Windows.Forms.DockStyle.Fill;
        this.lblEstado.Font = new System.Drawing.Font("Segoe UI", 10F);
        this.lblEstado.Padding = new System.Windows.Forms.Padding(12);
        this.lblEstado.Text = "Esperando órdenes del servidor (HabilitarSensor)…";
        this.lblEstado.TextAlign = System.Drawing.ContentAlignment.TopLeft;
        //
        // Form1
        //
        this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
        this.ClientSize = new System.Drawing.Size(640, 120);
        this.Controls.Add(this.lblEstado);
        this.Text = "Plugin biométrico — polling";
        this.ResumeLayout(false);
    }

    private System.Windows.Forms.Label lblEstado;

    #endregion
}
