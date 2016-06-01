<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output media-type="text/html" method="html" indent="yes" encoding="utf-8" omit-xml-declaration="yes" />

	<xsl:param name="upid" />

	<xsl:template match="siteMap">
		<ul>
			<xsl:apply-templates select="./siteMapNode[descendant-or-self::siteMapNode[@url=$upid]]" />
		</ul>
	</xsl:template>

	<xsl:template match="siteMapNode">
		<li>
			<xsl:attribute name="class">
				<xsl:if test="position() = 1"> first</xsl:if>
				<xsl:if test="position() = last()"> last</xsl:if>
				<xsl:if test="descendant::siteMapNode[@url=$upid]"> active</xsl:if>
				<xsl:if test="@url=$upid"> current</xsl:if>
			</xsl:attribute>
			<a>
				<xsl:attribute name="href"><xsl:value-of select="@url" /></xsl:attribute>
				<xsl:if test="@target != '_self'">
					<xsl:attribute name="target"><xsl:value-of select="@target" /></xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@title" />
			</a>
			<xsl:if test="siteMapNode and descendant-or-self::siteMapNode[@url=$upid]">
				<ul>
					<xsl:apply-templates select="./siteMapNode[descendant-or-self::siteMapNode[@url=$upid]]" />
				</ul>
			</xsl:if>
		</li>
	</xsl:template>

</xsl:stylesheet>